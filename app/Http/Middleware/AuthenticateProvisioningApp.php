<?php

namespace App\Http\Middleware;

use App\Models\Application;
use Closure;
use Illuminate\Http\Request;

class AuthenticateProvisioningApp
{
    /**
     * Handle an incoming request for connected application provisioning API.
     */
    public function handle(Request $request, Closure $next)
    {
        $clientId = $request->header('X-Client-Id') ?? $request->getUser();
        $clientSecret = $request->header('X-Client-Secret') ?? $request->getPassword();

        if (empty($clientId) || empty($clientSecret)) {
            // Check Bearer token alternative
            $bearer = $request->bearerToken();
            if ($bearer && $request->header('X-Client-Id')) {
                $clientId = $request->header('X-Client-Id');
                $clientSecret = $bearer;
            }
        }

        if (empty($clientId) || empty($clientSecret)) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Kredensial aplikasi (X-Client-Id & X-Client-Secret) diperlukan.',
            ], 401);
        }

        $app = Application::where('client_id', $clientId)->first();

        if (! $app || ! hash_equals($app->client_secret, $clientSecret)) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Kredensial aplikasi tidak valid.',
            ], 401);
        }

        if (! $app->is_active) {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'Aplikasi dalam status non-aktif di Gate SSO.',
            ], 403);
        }

        if (! $app->sync_enabled) {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'Fitur sinkronisasi belum diaktifkan untuk aplikasi ini.',
            ], 403);
        }

        $request->attributes->set('provisioning_app', $app);

        return $next($request);
    }
}
