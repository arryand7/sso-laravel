<?php

namespace App\Console\Commands;

use App\Support\UserPhotoImportPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class ApplyGateDataPatch extends Command
{
    protected $signature = 'gate:apply-data-patch {patch-id : Registered data patch identifier}';

    protected $description = 'Apply a registered, idempotent Gate production data patch';

    public function handle(): int
    {
        $patchId = (string) $this->argument('patch-id');

        if ($patchId !== UserPhotoImportPermissions::PATCH_ID) {
            $this->error("Unknown data patch: {$patchId}");

            return self::FAILURE;
        }

        try {
            $result = DB::transaction(fn (): array => $this->applyPhotoImportPermissions());
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $state = $result['created'] === 0 && $result['assigned'] === 0 ? 'NO-CHANGE' : 'APPLIED';
            $this->info(sprintf(
                '%s created=%d existing=%d assigned=%d unchanged=%d',
                $state,
                $result['created'],
                $result['existing'],
                $result['assigned'],
                $result['unchanged'],
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Data patch failed; no changes were committed.');

            return self::FAILURE;
        }
    }

    /**
     * The permission records and role-permission pivots are the durable patch state.
     *
     * @return array{created: int, existing: int, assigned: int, unchanged: int}
     */
    private function applyPhotoImportPermissions(): array
    {
        $role = Role::query()
            ->where('name', UserPhotoImportPermissions::ROLE)
            ->where('guard_name', UserPhotoImportPermissions::GUARD)
            ->firstOrFail();

        $result = ['created' => 0, 'existing' => 0, 'assigned' => 0, 'unchanged' => 0];

        foreach (UserPhotoImportPermissions::names() as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => UserPhotoImportPermissions::GUARD,
            ]);

            $permission->wasRecentlyCreated ? $result['created']++ : $result['existing']++;

            $alreadyAssigned = $role->permissions()
                ->whereKey($permission->getKey())
                ->exists();

            if ($alreadyAssigned) {
                $result['unchanged']++;
            } else {
                $role->givePermissionTo($permission);
                $result['assigned']++;
            }
        }

        return $result;
    }
}
