<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;

class WellKnownController extends Controller
{
    /**
     * Return OIDC discovery document.
     */
    public function openidConfiguration()
    {
        $issuer = rtrim((string) config('app.url'), '/');

        return response()->json([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer.'/oauth/authorize',
            'token_endpoint' => $issuer.'/oauth/token',
            'userinfo_endpoint' => $issuer.'/oauth/userinfo',
            'jwks_uri' => $issuer.'/.well-known/jwks.json',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => ['openid', 'profile', 'email', 'roles'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'claims_supported' => [
                'sub',
                'name',
                'email',
                'type',
                'roles',
                'nis',
                'nip',
            ],
        ]);
    }

    /**
     * Return JSON Web Key Set.
     */
    public function jwks()
    {
        $publicKey = $this->publicKey();

        if (! $publicKey) {
            return response()->json(['keys' => []]);
        }

        $keyInfo = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));

        if (! $keyInfo || ! isset($keyInfo['rsa'])) {
            return response()->json(['keys' => []]);
        }

        $keys = [
            [
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => md5($publicKey),
                'n' => $this->base64UrlEncode($keyInfo['rsa']['n']),
                'e' => $this->base64UrlEncode($keyInfo['rsa']['e']),
            ],
        ];

        return response()->json(['keys' => $keys]);
    }

    protected function publicKey(): ?string
    {
        $configured = config('passport.public_key');
        if ($configured) {
            return $this->normalizeKey($configured);
        }

        $publicKeyPath = storage_path('oauth-public.key');

        if (! file_exists($publicKeyPath)) {
            return null;
        }

        return file_get_contents($publicKeyPath) ?: null;
    }

    protected function normalizeKey(string $key): string
    {
        if (str_contains($key, '\\n')) {
            return str_replace('\\n', "\n", $key);
        }

        return $key;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
