<?php

namespace App\Jobs;

use App\Models\UserPhotoImportBatch;
use App\Services\UserPhotoImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ProcessUserPhotoImportBatch implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes

    public function __construct(
        public UserPhotoImportBatch $batch
    ) {}

    public function handle(UserPhotoImportService $service): void
    {
        $batch = $this->batch->fresh();
        if (! $batch || $batch->status !== 'importing') {
            return;
        }

        $chunkSize = Config::get('user-photo-import.chunk_size', 50);

        $query = $batch->items()
            ->whereIn('status', ['MATCHED_NEW', 'MATCHED_REPLACE']);

        $totalToProcess = $query->count();
        $processedCount = 0;
        $failedCount = 0;

        $query->chunk($chunkSize, function ($items) use ($service, &$processedCount, &$failedCount, $batch) {
            foreach ($items as $item) {
                $success = $service->processItem($item);
                $processedCount++;

                if (! $success) {
                    $failedCount++;
                }

                $batch->update([
                    'processed_count' => $processedCount,
                    'failed_count' => $batch->failed_count + ($success ? 0 : 1),
                ]);
            }
        });

        // Determine final batch status
        $finalStatus = $failedCount > 0 ? 'completed_with_errors' : 'completed';
        $batch->update([
            'status' => $finalStatus,
            'completed_at' => now(),
        ]);

        // Cleanup staging files
        $service->cleanupBatchFiles($batch);

        Log::info('Bulk photo import completed', [
            'batch_uuid' => $batch->uuid,
            'status' => $finalStatus,
            'processed' => $processedCount,
            'failed' => $failedCount,
        ]);
    }
}
