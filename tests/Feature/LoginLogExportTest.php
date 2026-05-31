<?php

namespace Tests\Feature;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginLogExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_filtered_login_logs_as_csv(): void
    {
        $admin = $this->adminUser();
        $user = User::create([
            'name' => 'Regular User',
            'username' => 'regular',
            'email' => 'regular@example.com',
            'password' => Hash::make('password'),
            'type' => 'staff',
            'status' => 'active',
        ]);

        LoginLog::create([
            'user_id' => $user->id,
            'client_app' => 'portal',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature Test',
            'login_at' => '2026-05-31 10:00:00',
        ]);

        LoginLog::create([
            'user_id' => $user->id,
            'client_app' => 'lms',
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Feature Test',
            'login_at' => '2026-05-31 11:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.logins.export', [
            'client_app' => 'portal',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Name,Username,Email,Application', $csv);
        $this->assertStringContainsString('"Regular User",regular,regular@example.com,portal', $csv);
        $this->assertStringNotContainsString(',lms,', $csv);
    }

    private function adminUser(): User
    {
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Admin',
            'username' => 'admin-test',
            'email' => 'admin-test@example.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
