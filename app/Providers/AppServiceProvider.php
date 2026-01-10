<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('oauth-token', function (Request $request) {
            $clientId = trim((string) $request->input('client_id', ''));
            $key = ($clientId !== '' ? $clientId : 'unknown').'|'.$request->ip();

            return [
                Limit::perMinute(20)->by($key),
                Limit::perHour(200)->by($key),
            ];
        });

        RateLimiter::for('login', function (Request $request) {
            $username = (string) $request->input('username', 'guest');
            return Limit::perMinute(10)->by(strtolower($username).'|'.$request->ip());
        });

        if (Schema::hasTable('settings')) {
            $emailSettings = Setting::group('email');

            if (!empty($emailSettings)) {
                $scheme = $this->normalizeMailScheme($emailSettings['scheme'] ?? config('mail.mailers.smtp.scheme'));

                config([
                    'mail.default' => $emailSettings['mailer'] ?? config('mail.default'),
                    'mail.mailers.smtp.host' => $emailSettings['host'] ?? config('mail.mailers.smtp.host'),
                    'mail.mailers.smtp.port' => $emailSettings['port'] ?? config('mail.mailers.smtp.port'),
                    'mail.mailers.smtp.username' => $emailSettings['username'] ?? config('mail.mailers.smtp.username'),
                    'mail.mailers.smtp.password' => $emailSettings['password'] ?? config('mail.mailers.smtp.password'),
                    'mail.mailers.smtp.scheme' => $scheme,
                    'mail.mailers.smtp.url' => null,
                    'mail.from.address' => $emailSettings['from_address'] ?? config('mail.from.address'),
                    'mail.from.name' => $emailSettings['from_name'] ?? config('mail.from.name'),
                ]);
            }

            $oauthSettings = Setting::group('oauth');
            if (!empty($oauthSettings)) {
                config([
                    'services.google.client_id' => $oauthSettings['google_client_id'] ?? config('services.google.client_id'),
                    'services.google.client_secret' => $oauthSettings['google_client_secret'] ?? config('services.google.client_secret'),
                    'services.google.redirect' => $oauthSettings['google_redirect_uri'] ?? config('services.google.redirect'),
                ]);
            }
        }
    }

    protected function normalizeMailScheme(?string $scheme): ?string
    {
        if (!$scheme) {
            return null;
        }

        $scheme = strtolower(trim($scheme));

        if ($scheme === 'ssl') {
            return 'smtps';
        }

        if ($scheme === 'tls') {
            return 'smtp';
        }

        return $scheme;
    }
}
