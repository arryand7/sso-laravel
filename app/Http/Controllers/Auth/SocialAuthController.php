<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider)
    {
        if ($provider !== 'google') {
            return $this->redirectWithError('Provider belum tersedia.');
        }

        if (!Schema::hasTable('settings') || !Setting::getBool('oauth', 'google_enabled')) {
            return $this->redirectWithError('Google OAuth tidak aktif.');
        }

        $config = $this->getGoogleConfig();
        if (!$config['client_id'] || !$config['client_secret'] || !$config['redirect']) {
            return $this->redirectWithError('Konfigurasi Google OAuth belum lengkap.');
        }

        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);
        $request->session()->put('oauth_continue', $request->query('continue'));

        $allowedDomains = $this->parseDomains($config['allowed_domains']);

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        if (count($allowedDomains) === 1) {
            $params['hd'] = $allowedDomains[0];
        }

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params));
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if ($provider !== 'google') {
            return $this->redirectWithError('Provider belum tersedia.');
        }

        if ($request->filled('error')) {
            return $this->redirectWithError('Google OAuth dibatalkan atau gagal.');
        }

        $state = $request->input('state');
        $sessionState = $request->session()->pull('oauth_state');

        if (!$state || !$sessionState || $state !== $sessionState) {
            return $this->redirectWithError('State OAuth tidak valid.');
        }

        $config = $this->getGoogleConfig();
        if (!$config['client_id'] || !$config['client_secret'] || !$config['redirect']) {
            return $this->redirectWithError('Konfigurasi Google OAuth belum lengkap.');
        }

        $code = $request->input('code');
        if (!$code) {
            return $this->redirectWithError('Kode otorisasi tidak ditemukan.');
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect'],
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResponse->ok()) {
            return $this->redirectWithError('Gagal mendapatkan token Google. '.$tokenResponse->body());
        }

        $accessToken = $tokenResponse->json('access_token');
        if (!$accessToken) {
            return $this->redirectWithError('Access token tidak ditemukan.');
        }

        $userInfo = Http::withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (!$userInfo->ok()) {
            return $this->redirectWithError('Gagal mengambil data profil Google.');
        }

        $email = $userInfo->json('email');
        if (!$email) {
            return $this->redirectWithError('Email Google tidak tersedia.');
        }

        $allowedDomains = $this->parseDomains($config['allowed_domains']);
        if (!$this->isAllowedDomain($email, $allowedDomains)) {
            return $this->redirectWithError('Domain email tidak diizinkan.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->redirectWithError('Akun belum terdaftar. Hubungi admin.');
        }

        if ($user->status !== 'active') {
            return $this->redirectWithError('Akun Anda tidak aktif. Hubungi admin.');
        }

        $user->update([
            'last_login_at' => now(),
            'name' => $user->name ?: $userInfo->json('name'),
        ]);

        Auth::login($user, true);
        LoginLog::recordLogin($user, 'google');

        $continue = $request->session()->pull('oauth_continue');
        if ($continue && $this->isSafeRedirect($continue)) {
            return redirect($continue);
        }

        return redirect()->route('dashboard');
    }

    protected function getGoogleConfig(): array
    {
        if (!Schema::hasTable('settings')) {
            return [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect' => config('services.google.redirect'),
                'allowed_domains' => '',
            ];
        }

        return [
            'client_id' => Setting::getValue('oauth', 'google_client_id'),
            'client_secret' => Setting::getValue('oauth', 'google_client_secret'),
            'redirect' => Setting::getValue('oauth', 'google_redirect_uri'),
            'allowed_domains' => Setting::getValue('oauth', 'google_allowed_domains', ''),
        ];
    }

    protected function parseDomains(?string $domains): array
    {
        if (!$domains) {
            return [];
        }

        return array_values(array_filter(array_map(function ($domain) {
            $domain = strtolower(trim($domain));
            return ltrim($domain, '@');
        }, explode(',', $domains))));
    }

    protected function isAllowedDomain(string $email, array $allowedDomains): bool
    {
        if (empty($allowedDomains)) {
            return true;
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        return in_array($domain, $allowedDomains, true);
    }

    protected function isSafeRedirect(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        return $host && $appHost && $host === $appHost;
    }

    protected function redirectWithError(string $message): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors(['oauth' => $message]);
    }
}
