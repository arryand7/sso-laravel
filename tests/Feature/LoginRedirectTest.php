<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_does_not_redirect_to_external_continue_url(): void
    {
        User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'type' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password',
            'continue' => 'https://evil.example/callback',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_allows_local_continue_url(): void
    {
        User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'type' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password',
            'continue' => '/profile',
        ]);

        $response->assertRedirect('/profile');
    }
}
