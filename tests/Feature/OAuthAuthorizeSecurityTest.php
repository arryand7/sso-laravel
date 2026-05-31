<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OAuthAuthorizeSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_client_does_not_redirect_to_untrusted_redirect_uri(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'type' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->getJson('/oauth/authorize?'.http_build_query([
            'client_id' => 'missing-client',
            'redirect_uri' => 'https://evil.example/callback',
            'response_type' => 'code',
            'scope' => 'openid',
            'state' => 'abc',
        ]));

        $response
            ->assertStatus(400)
            ->assertJson([
                'error' => 'invalid_client',
                'error_description' => 'Client not found or inactive.',
            ]);
    }
}
