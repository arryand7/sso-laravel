<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPhotoImportBatch;
use App\Models\UserPhotoImportItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserPhotoImportService
{
    public function __construct(
        protected UserPhotoZipInspector $inspector,
        protected UserPhotoMatcher $matcher,
        protected UserPhotoService $photoService
    ) {}

    /**
     * Upload ZIP file, inspect entries, run NIS/NIP matching, and create preview batch & items.
     * Note: This phase does NOT update users.photo_path or delete existing photos.
     */
    public function uploadAndInspect(
        UploadedFile $zipFile,
        string $matchingType,
        string $existingPolicy,
        int $uploadedBy
    ): UserPhotoImportBatch {
        $uuid = (string) Str::uuid();
        $disk = Config::get('user-photo-import.storage_disk', 'local');
        $baseDir = Config::get('user-photo-import.storage_directory', 'private/user-photo-imports');
        $zipStoredPath = $zipFile->storeAs($baseDir.'/'.$uuid, 'source.zip', $disk);

        $batch = UserPhotoImportBatch::create([
            'uuid' => $uuid,
            'matching_type' => $matchingType,
            'existing_photo_policy' => $existingPolicy,
            'original_filename' => $zipFile->getClientOriginalName(),
            'stored_zip_path' => $zipStoredPath,
            'status' => 'inspecting',
            'uploaded_by' => $uploadedBy,
            'expires_at' => now()->addHours(Config::get('user-photo-import.cleanup_expiration_hours', 24)),
        ]);

        try {
            // 1. Inspect & Extract ZIP
            $inspection = $this->inspector->inspectAndExtract($zipFile, $uuid);

            // 2. Match Files with Users
            $matchedItems = $this->matcher->matchFiles(
                $inspection['extracted_files'],
                $matchingType,
                $existingPolicy
            );

            // 3. Persist items to database
            $readyNewCount = 0;
            $readyReplaceCount = 0;
            $skippedCount = 0;
            $failedCount = 0;

            foreach ($matchedItems as $matchedItem) {
                UserPhotoImportItem::create([
                    'batch_id' => $batch->id,
                    'original_filename' => $matchedItem['original_filename'],
                    'temporary_path' => $matchedItem['temporary_path'],
                    'detected_extension' => $matchedItem['detected_extension'],
                    'detected_mime' => $matchedItem['detected_mime'],
                    'identifier_type' => $matchedItem['identifier_type'],
                    'identifier' => $matchedItem['identifier'],
                    'user_id' => $matchedItem['user_id'],
                    'status' => $matchedItem['status'],
                    'planned_action' => $matchedItem['planned_action'],
                    'error_code' => $matchedItem['error_code'],
                    'error_message' => $matchedItem['error_message'],
                    'old_photo_path' => $matchedItem['old_photo_path'],
                    'input_size' => $matchedItem['input_size'],
                ]);

                match ($matchedItem['status']) {
                    'MATCHED_NEW' => $readyNewCount++,
                    'MATCHED_REPLACE' => $readyReplaceCount++,
                    'SKIPPED_EXISTING' => $skippedCount++,
                    default => $failedCount++,
                };
            }

            // Update batch counts & status
            $batch->update([
                'status' => 'preview_ready',
                'total_entries' => $inspection['total_entries'],
                'total_photo_files' => count($matchedItems),
                'ready_new_count' => $readyNewCount,
                'ready_replace_count' => $readyReplaceCount,
                'skipped_count' => $skippedCount,
                'failed_count' => $failedCount,
            ]);

            Log::info('Bulk photo preview generated', [
                'actor' => $uploadedBy,
                'batch_uuid' => $uuid,
                'matching_type' => $matchingType,
                'policy' => $existingPolicy,
                'total_photos' => count($matchedItems),
                'ready_new' => $readyNewCount,
                'ready_replace' => $readyReplaceCount,
            ]);

            return $batch->refresh();
        } catch (\Exception $e) {
            $batch->update(['status' => 'failed']);
            $this->cleanupBatchFiles($batch);
            throw $e;
        }
    }

    /**
     * Process a single import item safely in an isolated transaction.
     */
    public function processItem(UserPhotoImportItem $item): bool
    {
        $item->refresh();

        if ($item->status === 'COMPLETED') {
            return true;
        }

        if (! $item->isImportable()) {
            return false;
        }

        $disk = Config::get('user-photo-import.storage_disk', 'local');
        $fullTempPath = Storage::disk($disk)->path($item->temporary_path);

        if (! file_exists($fullTempPath)) {
            $item->update([
                'status' => 'PROCESSING_FAILED',
                'error_code' => 'FILE_MISSING',
                'error_message' => 'File temporary tidak ditemukan.',
            ]);

            return false;
        }

        try {
            return DB::transaction(function () use ($item, $fullTempPath): bool {
                /** @var UserPhotoImportItem $lockedItem */
                $lockedItem = UserPhotoImportItem::query()->lockForUpdate()->findOrFail($item->getKey());

                if ($lockedItem->status === 'COMPLETED') {
                    return true;
                }

                if (! $lockedItem->isImportable()) {
                    return false;
                }

                /** @var User|null $user */
                $user = User::lockForUpdate()->find($lockedItem->user_id);
                if (! $user) {
                    throw new \RuntimeException('User tidak ditemukan saat pemrosesan.');
                }

                // Verify exact identifier still matches
                $currentIdentifier = $lockedItem->identifier_type === 'nis' ? $user->nis : $user->nip;
                if ((string) $currentIdentifier !== (string) $lockedItem->identifier) {
                    throw new \RuntimeException('NIS/NIP user telah berubah.');
                }

                // Process image via UserPhotoService
                $encodedData = $this->photoService->processPath($fullTempPath);

                // Store encoded photo and update DB
                $newPhotoPath = $this->photoService->storeEncoded($user, $encodedData);

                // Update item status
                $lockedItem->update([
                    'status' => 'COMPLETED',
                    'new_photo_path' => $newPhotoPath,
                    'output_size' => strlen($encodedData),
                    'processed_at' => now(),
                ]);

                Log::info('Photo processed through bulk import', [
                    'item_id' => $item->id,
                    'batch_id' => $item->batch_id,
                    'user_id' => $user->id,
                    'action' => $item->planned_action,
                    'old_photo' => $item->old_photo_path,
                    'new_photo' => $newPhotoPath,
                ]);
                $item->setRawAttributes($lockedItem->getAttributes(), true);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to process photo import item', [
                'item_id' => $item->id,
                'user_id' => $item->user_id,
                'error' => $e->getMessage(),
            ]);

            UserPhotoImportItem::query()
                ->whereKey($item->getKey())
                ->where('status', '!=', 'COMPLETED')
                ->update([
                    'status' => 'PROCESSING_FAILED',
                    'error_code' => 'PROCESSING_FAILED',
                    'error_message' => $e->getMessage(),
                ]);

            return false;
        }
    }

    /**
     * Cancel an import batch and delete temporary files.
     */
    public function cancelImport(UserPhotoImportBatch $batch): void
    {
        if (! $batch->isCancellable()) {
            throw new \InvalidArgumentException('Batch import tidak dapat dibatalkan pada status saat ini.');
        }

        $batch->update(['status' => 'cancelled']);
        $this->cleanupBatchFiles($batch);

        Log::info('Bulk photo import cancelled', [
            'actor' => auth()->id(),
            'batch_uuid' => $batch->uuid,
        ]);
    }

    /**
     * Clean up stored ZIP file and extracted temporary directory for a batch.
     */
    public function cleanupBatchFiles(UserPhotoImportBatch $batch): void
    {
        $disk = Config::get('user-photo-import.storage_disk', 'local');
        $baseDir = Config::get('user-photo-import.storage_directory', 'private/user-photo-imports');
        $batchDir = $baseDir.'/'.$batch->uuid;

        if (Storage::disk($disk)->exists($batchDir)) {
            Storage::disk($disk)->deleteDirectory($batchDir);
        }
    }
}
