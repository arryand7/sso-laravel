<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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

        $loginKey = $this->loginKey($request);
        if (RateLimiter::tooManyAttempts($loginKey, 5)) {
            $seconds = RateLimiter::availableIn($loginKey);
            $minutes = (int) ceil($seconds / 60);

            return back()->withErrors([
                'username' => 'Terlalu banyak percobaan login. Coba lagi dalam '.$minutes.' menit.',
            ])->withInput($request->only('username'));
        }

        // Try to authenticate with username or email
        $credentials = $request->only('password');
        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials[$loginField] = $request->username;

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($loginKey);
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

        RateLimiter::hit($loginKey, 300);

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

    protected function loginKey(Request $request): string
    {
        $username = strtolower((string) $request->input('username', 'guest'));

        return $username.'|'.$request->ip();
    }
}
