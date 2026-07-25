<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_admin_can_download_import_template_xlsx(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.users.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_unauthorized_user_cannot_download_import_template(): void
    {
        $response = $this->get(route('admin.users.import.template'));
        $response->assertRedirect(route('login'));
    }

    private function createAdmin(): User
    {
        $role = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['type' => 'admin']);
        $admin->assignRole($role);

        return $admin;
    }
}
