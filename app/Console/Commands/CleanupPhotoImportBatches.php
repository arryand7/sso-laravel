<?php

namespace App\Console\Commands;

use App\Models\UserPhotoImportBatch;
use App\Services\UserPhotoImportService;
use Illuminate\Console\Command;

class CleanupPhotoImportBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-photos:cleanup-imports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary files and expired records from user photo import batches';

    /**
     * Execute the console command.
     */
    public function handle(UserPhotoImportService $service): int
    {
        $this->info('Memulai pembersihan file temporary batch import foto user...');

        // 1. Mark expired uncommitted batches
        $expiredBatches = UserPhotoImportBatch::whereNotIn('status', ['completed', 'completed_with_errors', 'failed', 'cancelled', 'expired'])
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredBatches as $batch) {
            $batch->update(['status' => 'expired']);
            $service->cleanupBatchFiles($batch);
            $this->line("Batch {$batch->uuid} ditandai kedaluwarsa dan file temporary dibersihkan.");
        }

        // 2. Clean up files for terminal batches that still have leftover temp files
        $terminalBatches = UserPhotoImportBatch::whereIn('status', ['completed', 'completed_with_errors', 'failed', 'cancelled', 'expired'])
            ->get();

        $cleanedCount = 0;
        foreach ($terminalBatches as $batch) {
            $service->cleanupBatchFiles($batch);
            $cleanedCount++;
        }

        $this->info("Pembersihan selesai. {$cleanedCount} batch telah diverifikasi/dibersihkan.");

        return self::SUCCESS;
    }
}
