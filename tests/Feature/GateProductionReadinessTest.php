<?php

namespace Tests\Feature;

use App\Jobs\ProcessUserPhotoImportBatch;
use App\Support\UserPhotoImportPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GateProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_import_permission_patch_creates_only_required_permissions_and_assignments(): void
    {
        $role = Role::create(['name' => UserPhotoImportPermissions::ROLE, 'guard_name' => UserPhotoImportPermissions::GUARD]);
        $unrelatedRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $unrelatedPermission = Permission::create(['name' => 'unrelated.permission', 'guard_name' => 'web']);
        $unrelatedRole->givePermissionTo($unrelatedPermission);

        $this->artisan('gate:apply-data-patch', ['patch-id' => UserPhotoImportPermissions::PATCH_ID])
            ->expectsOutputToContain('APPLIED created=4 existing=0 assigned=4 unchanged=0')
            ->assertSuccessful();

        $this->assertSame(5, Permission::count());
        $this->assertSame(2, Role::count());
        $this->assertCount(4, $role->fresh()->permissions);
        $this->assertTrue($unrelatedRole->fresh()->hasPermissionTo($unrelatedPermission));
    }

    public function test_photo_import_permission_patch_is_a_successful_no_change_when_reapplied(): void
    {
        Role::create(['name' => UserPhotoImportPermissions::ROLE, 'guard_name' => UserPhotoImportPermissions::GUARD]);

        $this->artisan('gate:apply-data-patch', ['patch-id' => UserPhotoImportPermissions::PATCH_ID])->assertSuccessful();
        $permissionCount = Permission::count();

        $this->artisan('gate:apply-data-patch', ['patch-id' => UserPhotoImportPermissions::PATCH_ID])
            ->expectsOutputToContain('NO-CHANGE created=0 existing=4 assigned=0 unchanged=4')
            ->assertSuccessful();

        $this->assertSame($permissionCount, Permission::count());
    }

    public function test_patch_preserves_existing_permission_and_role(): void
    {
        $role = Role::create(['name' => UserPhotoImportPermissions::ROLE, 'guard_name' => UserPhotoImportPermissions::GUARD]);
        $existing = Permission::create([
            'name' => UserPhotoImportPermissions::names()[0],
            'guard_name' => UserPhotoImportPermissions::GUARD,
        ]);

        $this->artisan('gate:apply-data-patch', ['patch-id' => UserPhotoImportPermissions::PATCH_ID])
            ->expectsOutputToContain('APPLIED created=3 existing=1 assigned=4 unchanged=0')
            ->assertSuccessful();

        $this->assertTrue($existing->is(Permission::find($existing->id)));
        $this->assertTrue(Role::query()->whereKey($role->id)->exists());
    }

    public function test_database_queue_retry_window_exceeds_photo_import_timeout(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));
        $this->assertStringContainsString('QUEUE_CONNECTION=database', $envExample);
        $this->assertStringContainsString('QUEUE_RETRY_AFTER=2400', $envExample);
        $this->assertSame('user-photo-imports', config('user-photo-import.queue'));
        $this->assertGreaterThan(ProcessUserPhotoImportBatch::TIMEOUT, config('queue.connections.database.retry_after'));
        $this->assertSame(2400, config('queue.connections.database.retry_after'));
    }

    public function test_oauth_userinfo_without_token_is_json_unauthorized_and_proxy_trust_remains_configured(): void
    {
        $this->getJson('/oauth/userinfo')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringContainsString('$middleware->trustProxies(', $bootstrap);
        $this->assertStringContainsString('Request::HEADER_X_FORWARDED_PROTO', $bootstrap);
    }
}
