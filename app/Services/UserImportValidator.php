<?php

namespace App\Services;

use App\Models\User;

class UserImportValidator
{
    protected const VALID_TYPES = ['student', 'teacher', 'parent', 'staff', 'admin'];

    protected const VALID_STATUSES = ['active', 'suspended', 'pending'];

    protected const PROTECTED_ROLES = ['admin', 'superadmin'];

    protected const EXPECTED_HEADERS = [
        'username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'password', 'user_id',
    ];

    protected const TEMPLATE_VERSION = 'SABIRA_USER_IMPORT_V1';

    protected const MAX_ROWS = 5000;

    /**
     * Validate file structure (sheets, headers, template version).
     *
     * @return array List of structural errors (empty = OK).
     */
    public function validateStructure(array $sheets, ?string $templateVersion): array
    {
        $errors = [];

        if (! isset($sheets['Users']) && ! isset($sheets[0])) {
            $errors[] = ['code' => 'INVALID_HEADER', 'reason' => 'Sheet "Users" tidak ditemukan.'];

            return $errors;
        }

        if ($templateVersion !== null && $templateVersion !== self::TEMPLATE_VERSION) {
            $errors[] = [
                'code' => 'INVALID_TEMPLATE_VERSION',
                'reason' => "Versi template tidak valid: {$templateVersion}. Gunakan template resmi terbaru.",
            ];
        }

        return $errors;
    }

    /**
     * Validate headers match expected columns.
     *
     * @return array List of header errors (empty = OK).
     */
    public function validateHeaders(array $headers): array
    {
        $errors = [];
        $required = ['username', 'name', 'email', 'type', 'status'];

        foreach ($required as $col) {
            if (! in_array($col, $headers, true)) {
                $errors[] = [
                    'code' => 'INVALID_HEADER',
                    'reason' => "Kolom wajib \"{$col}\" tidak ditemukan di header.",
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate a single row and return list of errors.
     *
     * @return array List of error objects (empty = valid).
     */
    public function validateRow(array $row, int $rowNumber, string $mode, array $context): array
    {
        $errors = [];

        // Required fields
        $this->requireField($row, 'username', $rowNumber, $errors);
        $this->requireField($row, 'name', $rowNumber, $errors);
        $this->requireField($row, 'email', $rowNumber, $errors);
        $this->requireField($row, 'type', $rowNumber, $errors);
        $this->requireField($row, 'status', $rowNumber, $errors);

        // Email validation
        $email = trim($row['email'] ?? '');
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $this->error('email', $email, 'INVALID_EMAIL', 'Format email tidak valid.', 'Periksa format email.', $rowNumber);
        }

        // Type validation
        $type = strtolower(trim($row['type'] ?? ''));
        if ($type !== '' && ! in_array($type, self::VALID_TYPES, true)) {
            $errors[] = $this->error('type', $type, 'INVALID_ENUM', "Tipe \"{$type}\" tidak valid.", 'Gunakan: student, teacher, parent, staff, admin.', $rowNumber);
        }

        // Status validation
        $status = strtolower(trim($row['status'] ?? ''));
        if ($status !== '' && ! in_array($status, self::VALID_STATUSES, true)) {
            $errors[] = $this->error('status', $status, 'INVALID_ENUM', "Status \"{$status}\" tidak valid.", 'Gunakan: active, suspended, pending.', $rowNumber);
        }

        // Scientific notation detection for identifiers
        foreach (['nis', 'nip', 'username', 'qr_code'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '' && $this->isScientificNotation($value)) {
                $errors[] = $this->error($field, $value, 'SCIENTIFIC_NOTATION', "{$field} terbaca sebagai scientific notation.", 'Ubah format cell menjadi Text di Excel.', $rowNumber);
            }
        }

        // NIS required for student
        if ($type === 'student' && trim($row['nis'] ?? '') === '') {
            $errors[] = $this->error('nis', '', 'REQUIRED', 'NIS wajib diisi untuk tipe student.', 'Isi NIS.', $rowNumber);
        }

        // NIP required for teacher/staff
        if (in_array($type, ['teacher', 'staff'], true) && trim($row['nip'] ?? '') === '') {
            $errors[] = $this->error('nip', '', 'REQUIRED', 'NIP wajib diisi untuk tipe teacher/staff.', 'Isi NIP.', $rowNumber);
        }

        // user_id validation (if provided, must be numeric)
        $userId = trim($row['user_id'] ?? '');
        if ($userId !== '' && ! ctype_digit($userId)) {
            $errors[] = $this->error('user_id', $userId, 'INVALID_FORMAT', 'user_id harus berupa angka.', 'Gunakan ID internal user yang valid.', $rowNumber);
        }

        // Value length checks
        foreach (['username' => 255, 'name' => 255, 'email' => 255, 'nis' => 255, 'nip' => 255, 'qr_code' => 255] as $field => $max) {
            $value = trim((string) ($row[$field] ?? ''));
            if (mb_strlen($value) > $max) {
                $errors[] = $this->error($field, mb_substr($value, 0, 30).'...', 'VALUE_TOO_LONG', "{$field} melebihi {$max} karakter.", "Persingkat nilai {$field}.", $rowNumber);
            }
        }

        // Mode-specific validation
        if ($mode === 'update_only' && trim($row['user_id'] ?? '') === '') {
            // Try username lookup
            $username = trim($row['username'] ?? '');
            if ($username !== '' && ! isset($context['existing_usernames'][$username])) {
                $errors[] = $this->error('username', $username, 'USER_NOT_FOUND', 'User tidak ditemukan untuk mode Update.', 'Pastikan username sudah terdaftar atau sertakan user_id.', $rowNumber);
            }
        }

        if ($mode === 'create_only') {
            $username = trim($row['username'] ?? '');
            if ($username !== '' && isset($context['existing_usernames'][$username])) {
                $errors[] = $this->error('username', $username, 'DUPLICATE_DATABASE', 'Username sudah terdaftar (mode Create Only).', 'Gunakan mode Update jika ingin memperbarui.', $rowNumber);
            }
        }

        // QR uniqueness (against DB)
        $qrCode = trim($row['qr_code'] ?? '');
        if ($qrCode !== '' && isset($context['existing_qr_codes'][$qrCode])) {
            $existingUserId = $context['existing_qr_codes'][$qrCode];
            $currentUserId = trim($row['user_id'] ?? '');
            if ((string) $existingUserId !== $currentUserId) {
                $errors[] = $this->error('qr_code', $qrCode, 'DUPLICATE_DATABASE', 'QR code sudah digunakan user lain.', 'Gunakan QR code yang unik.', $rowNumber);
            }
        }

        // Email uniqueness (against DB)
        if ($email !== '' && isset($context['existing_emails'][$email])) {
            $existingUserId = $context['existing_emails'][$email];
            $currentUserId = trim($row['user_id'] ?? '');
            $currentUsername = trim($row['username'] ?? '');
            // Allow if updating the same user
            if ((string) $existingUserId !== $currentUserId) {
                $matchUser = isset($context['existing_usernames'][$currentUsername])
                    ? $context['existing_usernames'][$currentUsername]
                    : null;
                if ($matchUser !== $existingUserId) {
                    $errors[] = $this->error('email', $email, 'DUPLICATE_DATABASE', 'Email sudah digunakan user lain.', 'Gunakan email yang unik.', $rowNumber);
                }
            }
        }

        // Admin role protection
        if (in_array($type, ['admin'], true) && $mode === 'create_only') {
            $errors[] = $this->error('type', $type, 'UNAUTHORIZED_ROLE_CHANGE', 'Tipe admin tidak dapat dibuat melalui import.', 'Buat user admin secara manual.', $rowNumber);
        }

        // Identifier conflict detection
        $username = trim($row['username'] ?? '');
        $userId = trim($row['user_id'] ?? '');
        if ($username !== '' && $userId !== '') {
            $usernameOwner = $context['existing_usernames'][$username] ?? null;
            if ($usernameOwner !== null && (string) $usernameOwner !== $userId) {
                $errors[] = $this->error('username', $username, 'IDENTIFIER_CONFLICT', 'Username mengarah ke user berbeda dari user_id.', 'Periksa user_id dan username.', $rowNumber);
            }
        }

        return $errors;
    }

    /**
     * Detect in-file duplicates across all rows.
     *
     * @return array Errors keyed by row number.
     */
    public function detectInFileDuplicates(array $allRows): array
    {
        $errors = [];

        $seen = [
            'username' => [],
            'email' => [],
            'qr_code' => [],
        ];

        foreach ($allRows as $rowNumber => $row) {
            foreach (['username', 'email', 'qr_code'] as $field) {
                $value = strtolower(trim((string) ($row[$field] ?? '')));
                if ($value === '') {
                    continue;
                }

                if (isset($seen[$field][$value])) {
                    $firstRow = $seen[$field][$value];
                    $errors[$rowNumber][] = $this->error(
                        $field,
                        $row[$field] ?? '',
                        'DUPLICATE_IN_FILE',
                        "{$field} juga terdapat pada baris {$firstRow}.",
                        'Periksa data duplikat.',
                        $rowNumber
                    );
                } else {
                    $seen[$field][$value] = $rowNumber;
                }
            }
        }

        return $errors;
    }

    /**
     * Build context arrays from existing database data for validation.
     */
    public function buildValidationContext(array $allRows): array
    {
        $usernames = collect($allRows)->pluck('username')->filter()->map(fn ($v) => trim((string) $v))->unique()->values()->all();
        $emails = collect($allRows)->pluck('email')->filter()->map(fn ($v) => strtolower(trim((string) $v)))->unique()->values()->all();
        $qrCodes = collect($allRows)->pluck('qr_code')->filter()->map(fn ($v) => trim((string) $v))->unique()->values()->all();

        $existingUsernames = [];
        if ($usernames) {
            $existingUsernames = User::whereIn('username', $usernames)->pluck('id', 'username')->all();
        }

        $existingEmails = [];
        if ($emails) {
            $existingEmails = User::whereRaw('LOWER(email) IN ('.implode(',', array_fill(0, count($emails), '?')).')', $emails)
                ->pluck('id', 'email')
                ->mapWithKeys(fn ($id, $email) => [strtolower($email) => $id])
                ->all();
        }

        $existingQrCodes = [];
        if ($qrCodes) {
            $existingQrCodes = User::whereIn('qr_code', $qrCodes)->pluck('id', 'qr_code')->all();
        }

        return [
            'existing_usernames' => $existingUsernames,
            'existing_emails' => $existingEmails,
            'existing_qr_codes' => $existingQrCodes,
        ];
    }

    public function getExpectedHeaders(): array
    {
        return self::EXPECTED_HEADERS;
    }

    public function getTemplateVersion(): string
    {
        return self::TEMPLATE_VERSION;
    }

    public function getMaxRows(): int
    {
        return self::MAX_ROWS;
    }

    // ========== Private Helpers ==========

    protected function requireField(array $row, string $field, int $rowNumber, array &$errors): void
    {
        if (trim((string) ($row[$field] ?? '')) === '') {
            $errors[] = $this->error($field, '', 'REQUIRED', ucfirst($field).' belum diisi.', 'Isi '.$field.'.', $rowNumber);
        }
    }

    protected function error(string $field, string $value, string $code, string $reason, string $fix, int $rowNumber): array
    {
        return [
            'field' => $field,
            'value' => $value,
            'code' => $code,
            'reason' => $reason,
            'suggested_fix' => $fix,
            'row_number' => $rowNumber,
        ];
    }

    protected function isScientificNotation(string $value): bool
    {
        return (bool) preg_match('/^\d+(\.\d+)?[eE][+\-]?\d+$/', $value);
    }
}
