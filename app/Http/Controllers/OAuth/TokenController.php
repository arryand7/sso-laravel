<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\LoginLog;
use App\Models\User;
use App\Services\OidcTokenService;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Passport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TokenController extends Controller
{
    /**
     * Issue OAuth access token and append id_token for OpenID Connect.
     */
    public function issueToken(
        ServerRequestInterface $serverRequest,
        Request $request,
        ResponseInterface $response,
        OidcTokenService $oidc
    ) {
        $passportResponse = app(AccessTokenController::class)->issueToken($serverRequest, $response);

        $payload = json_decode($passportResponse->getContent(), true);

        if (! is_array($payload) || ! isset($payload['access_token'])) {
            return $passportResponse;
        }

        $tokenModel = Passport::tokenModel();
        $tokenId = $payload['access_token_id'] ?? $this->tokenIdFromJwt($payload['access_token']);
        $token = $tokenId ? $tokenModel::find($tokenId) : null;
        unset($payload['access_token_id']);

        if (! $token || ! $token->user_id) {
            return $passportResponse;
        }

        $scopes = is_array($token->scopes) ? $token->scopes : [];
        $requestedScopes = $request->input('scope')
            ? preg_split('/\s+/', trim((string) $request->input('scope')))
            : [];
        $scopes = array_values(array_unique(array_filter(array_merge($scopes, $requestedScopes))));

        if (! in_array('openid', $scopes, true)) {
            return $passportResponse;
        }

        $user = User::find($token->user_id);
        if (! $user) {
            return $passportResponse;
        }

        $payload['id_token'] = $oidc->issue(
            $user,
            (string) $token->client_id,
            $scopes,
            $token->expires_at,
            $request->input('nonce')
        );

        $application = Application::where('client_id', $token->client_id)->first();
        if ($application) {
            LoginLog::recordLogin($user, $application->slug);
        }

        return response()->json(
            $payload,
            $passportResponse->getStatusCode(),
            $passportResponse->headers->all()
        );
    }

    protected function tokenIdFromJwt(string $accessToken): ?string
    {
        $segments = explode('.', $accessToken);

        if (count($segments) < 2) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($segments[1]), true);

        return is_array($payload) && isset($payload['jti'])
            ? (string) $payload['jti']
            : null;
    }

    protected function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
