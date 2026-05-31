<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServerSettingsSecretTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_settings_page_does_not_render_stored_secrets(): void
    {
        $superadmin = $this->superadmin();

        Setting::setValue('oauth', 'google_client_secret', 'google-secret-value');
        Setting::setValue('email', 'password', 'smtp-secret-value');

        $response = $this->actingAs($superadmin)->get(route('admin.server.index'));

        $response->assertOk();
        $response->assertDontSee('google-secret-value', false);
        $response->assertDontSee('smtp-secret-value', false);
        $response->assertSee('Tersimpan - isi untuk mengganti');
    }

    public function test_blank_secret_fields_keep_existing_secret_values(): void
    {
        $superadmin = $this->superadmin();

        Setting::setValue('oauth', 'google_client_secret', 'old-google-secret');
        Setting::setValue('email', 'password', 'old-smtp-secret');

        $response = $this->actingAs($superadmin)->post(route('admin.server.update'), [
            'oauth' => [
                'google_enabled' => '1',
                'google_client_id' => 'google-client-id',
                'google_client_secret' => '',
            ],
            'email' => [
                'mailer' => 'smtp',
                'password' => '',
            ],
            'app' => [
                'timezone' => 'Asia/Jakarta',
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame('old-google-secret', Setting::getValue('oauth', 'google_client_secret'));
        $this->assertSame('old-smtp-secret', Setting::getValue('email', 'password'));
        $this->assertSame('google-client-id', Setting::getValue('oauth', 'google_client_id'));
    }

    private function superadmin(): User
    {
        $role = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Superadmin',
            'username' => 'superadmin-test',
            'email' => 'superadmin-test@example.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
