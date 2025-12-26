<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserInfoController extends Controller
{
    /**
     * Return user info for OIDC.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The access token is invalid.',
            ], 401);
        }

        return response()->json($user->getOidcClaims());
    }
}
