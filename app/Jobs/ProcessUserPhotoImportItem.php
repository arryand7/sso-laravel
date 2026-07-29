<?php

namespace App\Jobs;

use App\Models\UserPhotoImportItem;
use App\Services\UserPhotoImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessUserPhotoImportItem implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 60;

    public function __construct(
        public UserPhotoImportItem $item
    ) {
        $this->onQueue((string) config('user-photo-import.queue', 'user-photo-imports'));
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("user-photo-import-item:{$this->item->getKey()}"))
                ->releaseAfter($this->backoff)
                ->expireAfter(600),
        ];
    }

    public function handle(UserPhotoImportService $service): void
    {
        $service->processItem($this->item);
    }
}
