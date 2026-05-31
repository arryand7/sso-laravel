<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class OidcTokenService
{
    public function issue(User $user, string $clientId, array $scopes, ?\DateTimeInterface $expiresAt = null, ?string $nonce = null): string
    {
        $now = now();
        $exp = $expiresAt ? $expiresAt->getTimestamp() : $now->copy()->addHour()->getTimestamp();

        $claims = array_merge([
            'iss' => config('app.url'),
            'sub' => (string) $user->id,
            'aud' => $clientId,
            'iat' => $now->getTimestamp(),
            'exp' => $exp,
            'jti' => (string) Str::uuid(),
        ], $user->getOidcClaims());

        if ($nonce) {
            $claims['nonce'] = $nonce;
        }

        $header = array_filter([
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $this->keyId(),
        ]);

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];

        $signingInput = implode('.', $segments);
        $signature = $this->sign($signingInput);
        $segments[] = $signature;

        return implode('.', $segments);
    }

    protected function sign(string $payload): string
    {
        $privateKey = $this->getPrivateKey();

        $signature = '';
        if (! openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Unable to sign OIDC token.');
        }

        return $this->base64UrlEncode($signature);
    }

    protected function getPrivateKey()
    {
        $configured = config('passport.private_key');
        if ($configured) {
            $privateKey = openssl_pkey_get_private($this->normalizeKey($configured));

            if (! $privateKey) {
                throw new \RuntimeException('Configured Passport private key is invalid.');
            }

            return $privateKey;
        }

        $path = storage_path('oauth-private.key');

        if (! file_exists($path)) {
            throw new \RuntimeException('Passport private key not found. Run "php artisan passport:keys" or set PASSPORT_PRIVATE_KEY.');
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($path));

        if (! $privateKey) {
            throw new \RuntimeException('Passport private key is invalid.');
        }

        return $privateKey;
    }

    protected function keyId(): ?string
    {
        $configured = config('passport.public_key');
        if ($configured) {
            return md5($this->normalizeKey($configured));
        }

        $path = storage_path('oauth-public.key');

        if (! file_exists($path)) {
            return null;
        }

        return md5((string) file_get_contents($path));
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function normalizeKey(string $key): string
    {
        if (str_contains($key, '\\n')) {
            return str_replace('\\n', "\n", $key);
        }

        return $key;
    }
}
