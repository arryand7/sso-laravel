<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(Request $request)
    {
        $oauth = [
            'google' => false,
            'facebook' => false,
        ];

        if (Schema::hasTable('settings')) {
            $oauth = [
                'google' => Setting::getBool('oauth', 'google_enabled'),
                'facebook' => Setting::getBool('oauth', 'facebook_enabled'),
                'google_domains' => Setting::getValue('oauth', 'google_allowed_domains', ''),
            ];
        }

        return view('auth.login', [
            'continue' => $request->query('continue'),
            'oauth' => $oauth,
        ]);
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Try to authenticate with username or email
        $credentials = $request->only('password');
        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials[$loginField] = $request->username;

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            
            // Check if user is active
            if ($user->status !== 'active') {
                Auth::logout();
                return back()->withErrors([
                    'username' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
                ])->withInput($request->only('username'));
            }

            $request->session()->regenerate();
            
            // Update last login
            $user->update(['last_login_at' => now()]);
            
            // Record login log
            LoginLog::recordLogin($user, 'portal');

            // Redirect to continue URL or dashboard
            $continueUrl = $request->input('continue');
            if ($continueUrl && filter_var($continueUrl, FILTER_VALIDATE_URL)) {
                return redirect($continueUrl);
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->withInput($request->only('username'));
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
