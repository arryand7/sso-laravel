<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ServerController extends Controller
{
    public function index()
    {
        $oauthSettings = Setting::group('oauth');
        $emailSettings = Setting::group('email');
        $webSettings = Setting::group('web');

        return view('admin.server.index', [
            'oauth' => [
                'google_enabled' => Setting::getBool('oauth', 'google_enabled'),
                'google_client_id' => $oauthSettings['google_client_id'] ?? '',
                'google_client_secret' => $oauthSettings['google_client_secret'] ?? '',
                'google_redirect_uri' => $oauthSettings['google_redirect_uri'] ?? '',
                'google_allowed_domains' => $oauthSettings['google_allowed_domains'] ?? '',
                'facebook_enabled' => Setting::getBool('oauth', 'facebook_enabled'),
                'facebook_client_id' => $oauthSettings['facebook_client_id'] ?? '',
                'facebook_client_secret' => $oauthSettings['facebook_client_secret'] ?? '',
                'facebook_redirect_uri' => $oauthSettings['facebook_redirect_uri'] ?? '',
            ],
            'email' => [
                'mailer' => $emailSettings['mailer'] ?? config('mail.default'),
                'host' => $emailSettings['host'] ?? config('mail.mailers.smtp.host'),
                'port' => $emailSettings['port'] ?? config('mail.mailers.smtp.port'),
                'username' => $emailSettings['username'] ?? config('mail.mailers.smtp.username'),
                'password' => $emailSettings['password'] ?? '',
                'scheme' => $this->normalizeScheme($emailSettings['scheme'] ?? config('mail.mailers.smtp.scheme')),
                'from_address' => $emailSettings['from_address'] ?? config('mail.from.address'),
                'from_name' => $emailSettings['from_name'] ?? config('mail.from.name'),
                'reply_to' => $emailSettings['reply_to'] ?? '',
            ],
            'web' => [
                'api_docs_url' => $webSettings['api_docs_url'] ?? '',
                'postman_url' => $webSettings['postman_url'] ?? '',
            ],
            'endpoints' => [
                'issuer' => config('app.url'),
                'authorization' => url('/oauth/authorize'),
                'token' => url('/oauth/token'),
                'userinfo' => url('/oauth/userinfo'),
                'discovery' => url('/.well-known/openid-configuration'),
                'jwks' => url('/.well-known/jwks.json'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'oauth.google_enabled' => 'nullable|boolean',
            'oauth.google_client_id' => 'nullable|string|max:255',
            'oauth.google_client_secret' => 'nullable|string|max:255',
            'oauth.google_redirect_uri' => 'nullable|url|max:255',
            'oauth.google_allowed_domains' => 'nullable|string|max:255',
            'oauth.facebook_enabled' => 'nullable|boolean',
            'oauth.facebook_client_id' => 'nullable|string|max:255',
            'oauth.facebook_client_secret' => 'nullable|string|max:255',
            'oauth.facebook_redirect_uri' => 'nullable|url|max:255',
            'email.mailer' => 'nullable|string|max:50',
            'email.host' => 'nullable|string|max:255',
            'email.port' => 'nullable|integer|min:1|max:65535',
            'email.username' => 'nullable|string|max:255',
            'email.password' => 'nullable|string|max:255',
            'email.scheme' => 'nullable|string|max:20',
            'email.from_address' => 'nullable|email|max:255',
            'email.from_name' => 'nullable|string|max:255',
            'email.reply_to' => 'nullable|email|max:255',
            'email.test_address' => 'nullable|email|max:255',
            'web.api_docs_url' => 'nullable|url|max:255',
            'web.postman_url' => 'nullable|url|max:255',
        ]);

        $action = $request->input('action');
        $testAddress = $validated['email']['test_address'] ?? null;
        if (isset($validated['email']['test_address'])) {
            unset($validated['email']['test_address']);
        }

        if ($action === 'test-email') {
            if (!$testAddress) {
                return back()->withErrors(['email.test_address' => 'Alamat email tujuan wajib diisi.']);
            }

            try {
                $emailConfig = $this->buildEmailConfig($validated['email'] ?? []);
                $this->applyEmailConfig($emailConfig);
                app('mail.manager')->forgetMailers();

                $replyTo = $emailConfig['reply_to'] ?? null;

                Mail::raw(
                    'Ini adalah email uji coba dari Sabira Connect. Jika Anda menerima email ini, konfigurasi SMTP sudah berhasil.',
                    function ($message) use ($testAddress, $replyTo) {
                        $message->to($testAddress)
                            ->subject('Test Outgoing Mail - Sabira Connect');

                        if ($replyTo) {
                            $message->replyTo($replyTo);
                        }
                    }
                );
            } catch (Throwable $e) {
                return back()->withErrors([
                    'email.test_address' => 'Gagal mengirim email uji: '.$e->getMessage(),
                ]);
            }

            return back()->with('status', 'Email uji berhasil dikirim ke '.$testAddress.'.');
        }

        foreach (['oauth', 'email', 'web'] as $group) {
            foreach ($validated[$group] ?? [] as $key => $value) {
                if ($group === 'email' && $key === 'scheme') {
                    $value = $this->normalizeScheme($value);
                }
                Setting::setValue($group, $key, $value);
            }
        }

        return back()->with('status', 'Pengaturan server berhasil disimpan.');
    }

    protected function buildEmailConfig(array $input): array
    {
        $current = Setting::group('email');

        return [
            'mailer' => $this->prefer($input['mailer'] ?? null, $current['mailer'] ?? null, config('mail.default')),
            'host' => $this->prefer($input['host'] ?? null, $current['host'] ?? null, config('mail.mailers.smtp.host')),
            'port' => $this->prefer($input['port'] ?? null, $current['port'] ?? null, config('mail.mailers.smtp.port')),
            'username' => $this->prefer($input['username'] ?? null, $current['username'] ?? null, config('mail.mailers.smtp.username')),
            'password' => $this->prefer($input['password'] ?? null, $current['password'] ?? null, config('mail.mailers.smtp.password')),
            'scheme' => $this->normalizeScheme(
                $this->prefer($input['scheme'] ?? null, $current['scheme'] ?? null, config('mail.mailers.smtp.scheme'))
            ),
            'from_address' => $this->prefer($input['from_address'] ?? null, $current['from_address'] ?? null, config('mail.from.address')),
            'from_name' => $this->prefer($input['from_name'] ?? null, $current['from_name'] ?? null, config('mail.from.name')),
            'reply_to' => $this->prefer($input['reply_to'] ?? null, $current['reply_to'] ?? null, null),
        ];
    }

    protected function applyEmailConfig(array $config): void
    {
        config([
            'mail.default' => $config['mailer'],
            'mail.mailers.smtp.host' => $config['host'],
            'mail.mailers.smtp.port' => $config['port'],
            'mail.mailers.smtp.username' => $config['username'],
            'mail.mailers.smtp.password' => $config['password'],
            'mail.mailers.smtp.scheme' => $config['scheme'] ?: null,
            'mail.mailers.smtp.url' => null,
            'mail.from.address' => $config['from_address'],
            'mail.from.name' => $config['from_name'],
        ]);
    }

    protected function prefer($value, $fallback, $default = null)
    {
        if ($value === null || $value === '') {
            return $fallback ?? $default;
        }

        return $value;
    }

    protected function normalizeScheme(?string $scheme): ?string
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
