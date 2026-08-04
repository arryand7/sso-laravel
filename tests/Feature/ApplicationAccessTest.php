<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Models\UserApplicationAccess;
use App\Services\ApplicationAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Application $app1;

    protected Application $app2;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Gate',
            'username' => 'admin_gate',
            'email' => 'admin@sabira.id',
            'password' => 'secret123',
            'type' => 'admin',
            'status' => 'active',
        ]);
        $this->admin->assignRole('admin');

        $this->student = User::create([
            'name' => 'Siswa Test',
            'username' => 'student01',
            'email' => 'student01@sabira.id',
            'password' => 'secret123',
            'type' => 'student',
            'status' => 'active',
        ]);
        $this->student->assignRole('student');

        $this->app1 = Application::create([
            'name' => 'Smart Sabira',
            'slug' => 'smart',
            'base_url' => 'https://smart.sabira.id',
            'client_id' => 'smart-client-id',
            'client_secret' => 'smart-client-secret-12345678901234567890123456789012345678901234567890',
            'redirect_uri' => 'https://smart.sabira.id/callback',
            'is_active' => true,
        ]);

        $this->app2 = Application::create([
            'name' => 'Moodle LMS',
            'slug' => 'moodle',
            'base_url' => 'https://moodle.sabira.id',
            'client_id' => 'moodle-client-id',
            'client_secret' => 'moodle-client-secret-12345678901234567890123456789012345678901234567890',
            'redirect_uri' => 'https://moodle.sabira.id/callback',
            'is_active' => true,
        ]);
    }

    public function test_user_can_be_granted_application_access(): void
    {
        $service = app(ApplicationAccessService::class);
        $access = $service->grantAccess($this->student, $this->app1, 'santri', 'active', $this->admin->id);

        $this->assertInstanceOf(UserApplicationAccess::class, $access);
        $this->assertSame('active', $access->status);
        $this->assertSame('santri', $access->application_role);
        $this->assertSame($this->student->id, $access->user_id);
        $this->assertSame($this->app1->id, $access->application_id);

        $this->assertDatabaseHas('user_application_accesses', [
            'user_id' => $this->student->id,
            'application_id' => $this->app1->id,
            'application_role' => 'santri',
            'status' => 'active',
        ]);
    }

    public function test_user_access_can_be_updated_and_revoked(): void
    {
        $service = app(ApplicationAccessService::class);
        $service->grantAccess($this->student, $this->app1, 'santri', 'active', $this->admin->id);

        // Update role
        $service->updateAccess($this->student, $this->app1, ['application_role' => 'santri_senior']);
        $this->assertDatabaseHas('user_application_accesses', [
            'user_id' => $this->student->id,
            'application_id' => $this->app1->id,
            'application_role' => 'santri_senior',
        ]);

        // Revoke access
        $service->revokeAccess($this->student, $this->app1, $this->admin->id);
        $this->assertDatabaseHas('user_application_accesses', [
            'user_id' => $this->student->id,
            'application_id' => $this->app1->id,
            'status' => 'revoked',
        ]);
    }

    public function test_revoking_access_to_one_app_does_not_affect_other_apps(): void
    {
        $service = app(ApplicationAccessService::class);
        $service->grantAccess($this->student, $this->app1, 'santri', 'active');
        $service->grantAccess($this->student, $this->app2, 'student', 'active');

        // Revoke App1 only
        $service->revokeAccess($this->student, $this->app1, $this->admin->id);

        $this->assertDatabaseHas('user_application_accesses', [
            'user_id' => $this->student->id,
            'application_id' => $this->app1->id,
            'status' => 'revoked',
        ]);

        $this->assertDatabaseHas('user_application_accesses', [
            'user_id' => $this->student->id,
            'application_id' => $this->app2->id,
            'status' => 'active',
        ]);
    }

    public function test_user_detail_page_displays_application_accesses(): void
    {
        $service = app(ApplicationAccessService::class);
        $service->grantAccess($this->student, $this->app1, 'santri', 'active');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->student));

        $response->assertStatus(200);
        $response->assertSee('Smart Sabira');
        $response->assertSee('santri');
        $response->assertSee('Active');
    }

    public function test_application_detail_page_displays_users_with_access(): void
    {
        $service = app(ApplicationAccessService::class);
        $service->grantAccess($this->student, $this->app1, 'santri', 'active');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.applications.show', $this->app1));

        $response->assertStatus(200);
        $response->assertSee('Siswa Test');
        $response->assertSee('student01');
        $response->assertSee('santri');
    }

    public function test_bulk_access_modal_has_filter_and_select_all_controls(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.applications.show', $this->app1));

        $response->assertOk();
        $response->assertSee('bulk-user-search', false);
        $response->assertSee('bulk-user-type', false);
        $response->assertSee('bulk-user-select-all', false);
        $response->assertSee('Centang semua hasil filter');
        $response->assertSee('user_ids_json', false);
    }

    public function test_bulk_access_accepts_json_user_selection(): void
    {
        $secondStudent = User::create([
            'name' => 'Siswa Kedua',
            'username' => 'student02',
            'email' => 'student02@sabira.id',
            'password' => 'secret123',
            'type' => 'student',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.applications.users.bulk-grant', $this->app1),
            [
                'user_ids_json' => json_encode([$this->student->id, $secondStudent->id]),
                'application_role' => 'santri',
            ]
        );

        $response->assertRedirect(route('admin.applications.show', $this->app1));
        $response->assertSessionHas('status', 'Akses berhasil diberikan kepada 2 user.');
        $this->assertDatabaseHas('user_application_accesses', [
            'user_id' => $this->student->id,
            'application_id' => $this->app1->id,
            'application_role' => 'santri',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('user_application_accesses', [
            'user_id' => $secondStudent->id,
            'application_id' => $this->app1->id,
            'application_role' => 'santri',
            'status' => 'active',
        ]);
    }

    public function test_bulk_access_service_handles_more_than_one_thousand_users(): void
    {
        $users = User::factory()->count(1205)->create();

        $count = app(ApplicationAccessService::class)->bulkGrantAccess(
            $users->pluck('id')->all(),
            $this->app1,
            'staff',
            $this->admin->id
        );

        $this->assertSame(1205, $count);
        $this->assertDatabaseCount('user_application_accesses', 1205);
        $this->assertDatabaseCount('application_user_sync_statuses', 1205);
    }
}
