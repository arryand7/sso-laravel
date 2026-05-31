<?php

namespace Tests\Feature;

use Tests\TestCase;

class OidcDiscoveryTest extends TestCase
{
    public function test_openid_configuration_uses_normalized_issuer_and_endpoints(): void
    {
        config(['app.url' => 'https://gate.example.test/']);

        $response = $this->getJson('/.well-known/openid-configuration');

        $response
            ->assertOk()
            ->assertJson([
                'issuer' => 'https://gate.example.test',
                'authorization_endpoint' => 'https://gate.example.test/oauth/authorize',
                'token_endpoint' => 'https://gate.example.test/oauth/token',
                'userinfo_endpoint' => 'https://gate.example.test/oauth/userinfo',
                'jwks_uri' => 'https://gate.example.test/.well-known/jwks.json',
                'response_types_supported' => ['code'],
                'id_token_signing_alg_values_supported' => ['RS256'],
            ]);
    }

    public function test_jwks_uses_configured_passport_public_key(): void
    {
        $keys = $this->rsaKeyPair();
        config(['passport.public_key' => $keys['public']]);

        $response = $this->getJson('/.well-known/jwks.json');

        $response
            ->assertOk()
            ->assertJsonPath('keys.0.kty', 'RSA')
            ->assertJsonPath('keys.0.alg', 'RS256')
            ->assertJsonPath('keys.0.use', 'sig')
            ->assertJsonPath('keys.0.kid', md5($keys['public']));

        $this->assertNotEmpty($response->json('keys.0.n'));
        $this->assertNotEmpty($response->json('keys.0.e'));
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
