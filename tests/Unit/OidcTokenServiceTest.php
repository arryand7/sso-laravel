<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OidcTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OidcTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_token_is_signed_with_configured_key_and_contains_expected_claims(): void
    {
        $keys = $this->rsaKeyPair();
        config([
            'app.url' => 'https://gate.example.test',
            'passport.private_key' => $keys['private'],
            'passport.public_key' => $keys['public'],
        ]);

        $user = User::create([
            'name' => 'OIDC User',
            'username' => 'oidc-user',
            'email' => 'oidc@example.test',
            'password' => Hash::make('password'),
            'type' => 'staff',
            'status' => 'active',
        ]);

        $token = app(OidcTokenService::class)->issue(
            $user,
            'client-123',
            ['openid', 'profile', 'email'],
            now()->addMinutes(30),
            'nonce-123'
        );

        [$header, $claims, $signature, $signingInput] = $this->decodeJwt($token);

        $this->assertSame('RS256', $header['alg']);
        $this->assertSame(md5($keys['public']), $header['kid']);
        $this->assertSame('https://gate.example.test', $claims['iss']);
        $this->assertSame((string) $user->id, $claims['sub']);
        $this->assertSame('client-123', $claims['aud']);
        $this->assertSame('OIDC User', $claims['name']);
        $this->assertSame('oidc@example.test', $claims['email']);
        $this->assertSame('nonce-123', $claims['nonce']);
        $this->assertSame(1, openssl_verify($signingInput, $signature, $keys['public'], OPENSSL_ALGO_SHA256));
    }

    private function decodeJwt(string $jwt): array
    {
        $segments = explode('.', $jwt);

        $header = json_decode($this->base64UrlDecode($segments[0]), true);
        $claims = json_decode($this->base64UrlDecode($segments[1]), true);
        $signature = $this->base64UrlDecode($segments[2]);
        $signingInput = $segments[0].'.'.$segments[1];

        return [$header, $claims, $signature, $signingInput];
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }

    private function rsaKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($resource, $private);
        $details = openssl_pkey_get_details($resource);

        return [
            'private' => $private,
            'public' => $details['key'],
        ];
    }
}
