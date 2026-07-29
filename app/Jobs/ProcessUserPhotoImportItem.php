<?php

namespace App\Jobs;

use App\Models\UserPhotoImportItem;
use App\Services\UserPhotoImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUserPhotoImportItem implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public UserPhotoImportItem $item
    ) {}

    public function handle(UserPhotoImportService $service): void
    {
        $service->processItem($this->item);
    }
}
