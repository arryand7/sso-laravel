<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WellKnownController extends Controller
{
    /**
     * Return OIDC discovery document.
     */
    public function openidConfiguration()
    {
        $issuer = config('app.url');

        return response()->json([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer . '/oauth/authorize',
            'token_endpoint' => $issuer . '/oauth/token',
            'userinfo_endpoint' => $issuer . '/oauth/userinfo',
            'jwks_uri' => $issuer . '/.well-known/jwks.json',
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
        // Get Passport's public key
        $publicKeyPath = storage_path('oauth-public.key');
        
        if (!file_exists($publicKeyPath)) {
            return response()->json(['keys' => []]);
        }

        $publicKey = file_get_contents($publicKeyPath);
        $keyInfo = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));

        if (!$keyInfo || !isset($keyInfo['rsa'])) {
            return response()->json(['keys' => []]);
        }

        $keys = [
            [
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => md5($publicKey),
                'n' => rtrim(strtr(base64_encode($keyInfo['rsa']['n']), '+/', '-_'), '='),
                'e' => rtrim(strtr(base64_encode($keyInfo['rsa']['e']), '+/', '-_'), '='),
            ],
        ];

        return response()->json(['keys' => $keys]);
    }
}
