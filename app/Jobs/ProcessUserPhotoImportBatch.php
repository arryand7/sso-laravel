<?php

namespace App\Jobs;

use App\Models\UserPhotoImportBatch;
use App\Services\UserPhotoImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ProcessUserPhotoImportBatch implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public const TIMEOUT = 1800;

    public int $timeout = self::TIMEOUT;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public UserPhotoImportBatch $batch
    ) {
        $this->onQueue((string) config('user-photo-import.queue', 'user-photo-imports'));
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("user-photo-import-batch:{$this->batch->getKey()}"))
                ->releaseAfter($this->backoff)
                ->expireAfter($this->timeout + 300),
        ];
    }

    public function handle(UserPhotoImportService $service): void
    {
        $batch = $this->batch->fresh();
        if (! $batch || $batch->status !== 'importing') {
            return;
        }

        $chunkSize = Config::get('user-photo-import.chunk_size', 50);

        $query = $batch->items()
            ->whereIn('status', ['MATCHED_NEW', 'MATCHED_REPLACE']);

        $query->chunkById($chunkSize, function ($items) use ($service, $batch) {
            foreach ($items as $item) {
                $service->processItem($item);

                $batch->update([
                    'processed_count' => $batch->items()->where('status', 'COMPLETED')->count(),
                    'failed_count' => $batch->items()->where('status', 'PROCESSING_FAILED')->count(),
                ]);
            }
        });

        $processedCount = $batch->items()->where('status', 'COMPLETED')->count();
        $failedCount = $batch->items()->where('status', 'PROCESSING_FAILED')->count();

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

    public function failed(\Throwable $exception): void
    {
        $batch = $this->batch->fresh();

        if ($batch?->status === 'importing') {
            $batch->update(['status' => 'failed', 'completed_at' => now()]);
        }

        Log::error('Bulk photo import job failed', [
            'batch_uuid' => $batch?->uuid,
            'exception' => $exception::class,
        ]);
    }
}
