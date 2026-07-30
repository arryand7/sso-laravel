<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Services\ApplicationPopulationPreviewService;
use Illuminate\Console\Command;

class PreviewApplicationPopulation extends Command
{
    protected $signature = 'gate:preview-application-population {application : Application slug}';

    protected $description = 'Preview the read-only identity population assigned to an application';

    public function handle(ApplicationPopulationPreviewService $service): int
    {
        $application = Application::where('slug', $this->argument('application'))->first();

        if (! $application) {
            $this->error('Application not found: '.$this->argument('application'));

            return self::FAILURE;
        }

        $preview = $service->preview($application);
        $duplicateGroups = $preview['duplicate_identity_groups'];
        unset($preview['duplicate_identity_groups']);

        $this->table(['Metric', 'Count / Value'], collect($preview)->map(
            fn ($value, $key) => [$key, $value]
        )->values()->all());

        if ($duplicateGroups !== []) {
            $this->newLine();
            $this->warn('Duplicate identity groups (review only; no data was changed):');
            $this->table(
                ['Identity type', 'Identity value', 'Gate user IDs'],
                collect($duplicateGroups)->map(fn (array $group) => [
                    $group['identity_type'],
                    $group['identity_value'],
                    implode(', ', $group['user_ids']),
                ])->all()
            );
        }

        $this->info('Read-only preview complete. No assignments were created or changed.');

        return self::SUCCESS;
    }
}
