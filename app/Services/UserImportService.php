<?php

namespace App\Services;

use App\Exports\UserImportReportExport;
use App\Models\User;
use App\Models\UserImportBatch;
use App\Models\UserImportRow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class UserImportService
{
    protected const PROTECTED_ADMIN_ROLES = ['admin', 'superadmin'];

    protected const MANAGED_IDENTITY_ROLES = ['student', 'teacher', 'staff', 'parent'];

    public function __construct(
        protected UserImportValidator $validator
    ) {}

    /**
     * Upload and create a new import batch.
     */
    public function upload(UploadedFile $file, int $uploadedBy, string $mode = 'create_only'): UserImportBatch
    {
        $this->validateFile($file);

        $uuid = (string) Str::uuid();
        $hash = hash_file('sha256', $file->getPathname());
        $storedPath = $file->storeAs('imports/'.$uuid, 'source.xlsx', 'local');

        $batch = UserImportBatch::create([
            'uuid' => $uuid,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mode' => $mode,
            'status' => 'uploaded',
            'uploaded_by' => $uploadedBy,
            'source_file_hash' => $hash,
        ]);

        return $batch;
    }

    /**
     * Validate and parse the uploaded file, creating import rows.
     */
    public function validate(UserImportBatch $batch): UserImportBatch
    {
        $batch->update(['status' => 'validating']);

        try {
            $filePath = Storage::disk('local')->path($batch->stored_path);

            // Read all sheets
            $sheets = $this->readSheets($filePath);

            // Find template version marker
            $templateVersion = $this->findTemplateVersion($sheets);
            $batch->update(['template_version' => $templateVersion]);

            // Validate structure
            $structuralErrors = $this->validator->validateStructure($sheets, $templateVersion);
            if (! empty($structuralErrors)) {
                $batch->update([
                    'status' => 'validation_failed',
                    'invalid_rows' => 0,
                ]);
                // Store structural errors as a single row with row_number 0
                UserImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => 0,
                    'payload' => [],
                    'status' => 'invalid',
                    'errors' => $structuralErrors,
                ]);

                return $batch->refresh();
            }

            // Read the Users sheet data
            $usersData = $sheets['Users'] ?? $sheets[0] ?? [];
            if (empty($usersData)) {
                $batch->update(['status' => 'validation_failed']);
                UserImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => 0,
                    'payload' => [],
                    'status' => 'invalid',
                    'errors' => [['code' => 'INVALID_HEADER', 'reason' => 'Sheet Users kosong.']],
                ]);

                return $batch->refresh();
            }

            // Parse header row
            $rawHeaders = array_map(fn ($h) => strtolower(trim((string) $h)), $usersData[0] ?? []);
            $headerErrors = $this->validator->validateHeaders($rawHeaders);
            if (! empty($headerErrors)) {
                $batch->update(['status' => 'validation_failed']);
                UserImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => 1,
                    'payload' => ['headers' => $rawHeaders],
                    'status' => 'invalid',
                    'errors' => $headerErrors,
                ]);

                return $batch->refresh();
            }

            // Map rows to associative arrays
            $dataRows = [];
            $headerMap = $rawHeaders;
            for ($i = 1; $i < count($usersData); $i++) {
                $row = $usersData[$i];
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $mapped = [];
                foreach ($headerMap as $colIndex => $headerName) {
                    $mapped[$headerName] = trim((string) ($row[$colIndex] ?? ''));
                }
                $dataRows[$i + 1] = $mapped; // row_number = Excel row (1-indexed header + data)
            }

            // Check row count
            if (count($dataRows) > $this->validator->getMaxRows()) {
                $batch->update(['status' => 'validation_failed']);
                UserImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => 0,
                    'payload' => [],
                    'status' => 'invalid',
                    'errors' => [['code' => 'INVALID_FORMAT', 'reason' => 'Jumlah baris ('.count($dataRows).') melebihi batas '.$this->validator->getMaxRows().'.']],
                ]);

                return $batch->refresh();
            }

            // Build validation context from DB
            $context = $this->validator->buildValidationContext($dataRows);

            // Detect in-file duplicates
            $inFileDuplicates = $this->validator->detectInFileDuplicates($dataRows);

            // Validate each row
            $validCount = 0;
            $invalidCount = 0;

            // Delete old rows if re-validating
            $batch->rows()->delete();

            foreach ($dataRows as $rowNumber => $row) {
                $rowErrors = $this->validator->validateRow($row, $rowNumber, $batch->mode, $context);

                // Merge in-file duplicate errors
                if (isset($inFileDuplicates[$rowNumber])) {
                    $rowErrors = array_merge($rowErrors, $inFileDuplicates[$rowNumber]);
                }

                $isValid = empty($rowErrors);
                $action = $this->determineAction($row, $batch->mode, $context);

                UserImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'identifier' => $row['username'] ?? null,
                    'payload' => $row,
                    'action' => $action,
                    'status' => $isValid ? 'valid' : 'invalid',
                    'errors' => $isValid ? null : $rowErrors,
                ]);

                $isValid ? $validCount++ : $invalidCount++;
            }

            $batch->update([
                'total_rows' => $validCount + $invalidCount,
                'valid_rows' => $validCount,
                'invalid_rows' => $invalidCount,
                'status' => $invalidCount > 0 ? 'validation_failed' : 'ready',
            ]);

            return $batch->refresh();

        } catch (\Exception $e) {
            $batch->update(['status' => 'validation_failed']);
            UserImportRow::create([
                'batch_id' => $batch->id,
                'row_number' => 0,
                'payload' => [],
                'status' => 'invalid',
                'errors' => [['code' => 'INVALID_FORMAT', 'reason' => 'Gagal membaca file: '.$e->getMessage()]],
            ]);

            return $batch->refresh();
        }
    }

    /**
     * Commit a validated batch — atomic import.
     */
    public function commit(UserImportBatch $batch): UserImportBatch
    {
        if (! $batch->isCommittable()) {
            throw new \RuntimeException('Batch tidak dalam status siap import.');
        }

        $batch->update([
            'status' => 'importing',
            'started_at' => now(),
        ]);

        try {
            $created = 0;
            $updated = 0;
            $failed = 0;

            DB::transaction(function () use ($batch, &$created, &$updated, &$failed): void {
                $validRows = $batch->rows()->where('status', 'valid')->orderBy('row_number')->get();

                foreach ($validRows as $importRow) {
                    $result = $this->processRow($importRow, $batch->mode);

                    if ($result === 'created') {
                        $created++;
                        $importRow->update(['status' => 'created']);
                    } elseif ($result === 'updated') {
                        $updated++;
                        $importRow->update(['status' => 'updated']);
                    } else {
                        $failed++;
                        $importRow->update(['status' => 'failed']);
                    }
                }

                // If any failed within the transaction, rollback everything
                if ($failed > 0) {
                    throw new \RuntimeException("Import gagal: {$failed} baris gagal diproses.");
                }
            });

            $batch->update([
                'status' => 'completed',
                'created_rows' => $created,
                'updated_rows' => $updated,
                'failed_rows' => $failed,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'failed_rows' => $batch->rows()->where('status', 'valid')->count(),
                'completed_at' => now(),
            ]);

            throw $e;
        }

        return $batch->refresh();
    }

    /**
     * Cancel a batch.
     */
    public function cancel(UserImportBatch $batch): void
    {
        if (! $batch->isCancellable()) {
            throw new \RuntimeException('Batch tidak dapat dibatalkan.');
        }

        $batch->update(['status' => 'cancelled']);

        // Clean up stored file
        if ($batch->stored_path) {
            Storage::disk('local')->delete($batch->stored_path);
        }
    }

    /**
     * Generate and store the error report XLSX, return download path.
     */
    public function generateReport(UserImportBatch $batch): string
    {
        $uuid = $batch->uuid;
        $reportPath = "imports/{$uuid}/report.xlsx";

        $export = new UserImportReportExport($batch);
        Excel::store($export, $reportPath, 'local');

        $batch->update(['report_path' => $reportPath]);

        return $reportPath;
    }

    // ========== Private Methods ==========

    protected function validateFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'xlsx') {
            throw new \InvalidArgumentException('Hanya file XLSX yang diterima.');
        }

        $mime = $file->getMimeType();
        $validMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip', // XLSX is a ZIP container
        ];
        if (! in_array($mime, $validMimes, true)) {
            throw new \InvalidArgumentException('Tipe file tidak valid. Gunakan file XLSX.');
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('Ukuran file melebihi 10 MB.');
        }
    }

    protected function readSheets(string $filePath): array
    {
        $allSheets = [];

        $sheets = Excel::toArray(new class implements ToArray
        {
            public function array(array $array) {}
        }, $filePath);

        // Try to get sheet names
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        foreach ($reader->getSheetNames() as $index => $name) {
            $allSheets[$name] = $sheets[$index] ?? [];
        }

        return $allSheets;
    }

    protected function findTemplateVersion(array $sheets): ?string
    {
        $usersSheet = $sheets['Users'] ?? $sheets[0] ?? [];
        if (empty($usersSheet) || empty($usersSheet[0])) {
            return null;
        }

        // Look for template version marker in the first row cells
        foreach ($usersSheet[0] as $cell) {
            $value = trim((string) $cell);
            if (str_starts_with($value, 'SABIRA_USER_IMPORT_')) {
                return $value;
            }
        }

        return null;
    }

    protected function isEmptyRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    protected function determineAction(array $row, string $mode, array $context): string
    {
        $username = trim($row['username'] ?? '');
        $userId = trim($row['user_id'] ?? '');

        $exists = false;
        if ($userId !== '' && ctype_digit($userId)) {
            $exists = User::where('id', (int) $userId)->exists();
        } elseif ($username !== '') {
            $exists = isset($context['existing_usernames'][$username]);
        }

        if ($mode === 'create_only') {
            return 'create';
        }
        if ($mode === 'update_only') {
            return 'update';
        }

        // create_and_update
        return $exists ? 'update' : 'create';
    }

    protected function processRow(UserImportRow $importRow, string $mode): string
    {
        $data = $importRow->payload;
        $username = trim($data['username'] ?? '');
        $userId = trim($data['user_id'] ?? '');
        $type = strtolower(trim($data['type'] ?? 'staff'));
        $status = strtolower(trim($data['status'] ?? 'active'));

        // Find existing user
        $user = null;
        if ($userId !== '' && ctype_digit($userId)) {
            $user = User::find((int) $userId);
        }
        if (! $user && $username !== '') {
            $user = User::where('username', $username)->first();
        }

        $payload = [
            'name' => trim($data['name'] ?? ''),
            'username' => $username,
            'email' => trim($data['email'] ?? '') ?: null,
            'type' => $type,
            'nis' => trim($data['nis'] ?? '') ?: null,
            'nip' => trim($data['nip'] ?? '') ?: null,
            'status' => $status,
        ];

        // QR code
        $qrCode = trim($data['qr_code'] ?? '');
        if ($qrCode !== '') {
            $payload['qr_code'] = $qrCode;
        }

        // Password handling
        $password = trim($data['password'] ?? '');

        if ($importRow->action === 'create' && ! $user) {
            $payload['password'] = Hash::make($password !== '' ? $password : Str::random(16));
            $user = User::create($payload);
            $this->syncManagedRoles($user, $type);
            $importRow->update(['user_id' => $user->id]);

            return 'created';
        }

        if ($user && in_array($importRow->action, ['update', 'create'], true)) {
            if ($password !== '') {
                $payload['password'] = Hash::make($password);
            }
            $user->update($payload);
            $this->syncManagedRoles($user, $type);
            $importRow->update(['user_id' => $user->id]);

            return 'updated';
        }

        return 'failed';
    }

    /**
     * Sync only managed identity roles without removing administrative roles.
     */
    protected function syncManagedRoles(User $user, string $type): void
    {
        // Get current admin roles the user has
        $currentAdminRoles = $user->roles()
            ->whereIn('name', self::PROTECTED_ADMIN_ROLES)
            ->pluck('roles.id')
            ->toArray();

        // Determine the new identity role
        $identityRole = Role::where('name', $type)
            ->where('guard_name', 'web')
            ->first();

        if (! $identityRole) {
            return;
        }

        // Remove only managed identity roles, keep admin roles
        $managedRoleIds = Role::whereIn('name', self::MANAGED_IDENTITY_ROLES)
            ->pluck('id')
            ->toArray();

        $user->roles()->detach($managedRoleIds);

        // Attach the new identity role
        $user->roles()->syncWithoutDetaching([$identityRole->id]);

        // Re-attach admin roles if they existed
        if (! empty($currentAdminRoles)) {
            $user->roles()->syncWithoutDetaching($currentAdminRoles);
        }
    }
}
