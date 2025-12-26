@extends('layouts.admin')
@section('page-title', 'Server Settings')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Server Module</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Pengaturan layanan inti untuk Sabira Connect (khusus super admin).
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.server.update') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-primary">security</span>
                <div>
                    <h2 class="text-lg font-bold">OAuth 2 Services</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Aktifkan penyedia login eksternal seperti Google atau Facebook.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-lg border border-slate-100 dark:border-slate-800 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-200">Google OAuth</h3>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <input type="hidden" name="oauth[google_enabled]" value="0">
                            <input type="checkbox" name="oauth[google_enabled]" value="1" class="rounded border-slate-300 text-primary" {{ $oauth['google_enabled'] ? 'checked' : '' }}>
                            Aktif
                        </label>
                    </div>
                    <div class="grid gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Client ID</label>
                            <input type="text" name="oauth[google_client_id]" value="{{ old('oauth.google_client_id', $oauth['google_client_id']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Client Secret</label>
                            <input type="password" name="oauth[google_client_secret]" value="{{ old('oauth.google_client_secret', $oauth['google_client_secret']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Redirect URI</label>
                            <input type="url" name="oauth[google_redirect_uri]" value="{{ old('oauth.google_redirect_uri', $oauth['google_redirect_uri']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" placeholder="https://gate.sabira-iibs.id/auth/google/callback">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Allowed Domains</label>
                            <input type="text" name="oauth[google_allowed_domains]" value="{{ old('oauth.google_allowed_domains', $oauth['google_allowed_domains']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" placeholder="sabira-iibs.id, sabira.sch.id">
                            <p class="text-xs text-slate-400 mt-2">Pisahkan dengan koma untuk mengizinkan banyak domain.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-100 dark:border-slate-800 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-200">Facebook OAuth</h3>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <input type="hidden" name="oauth[facebook_enabled]" value="0">
                            <input type="checkbox" name="oauth[facebook_enabled]" value="1" class="rounded border-slate-300 text-primary" {{ $oauth['facebook_enabled'] ? 'checked' : '' }}>
                            Aktif
                        </label>
                    </div>
                    <div class="grid gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">App ID</label>
                            <input type="text" name="oauth[facebook_client_id]" value="{{ old('oauth.facebook_client_id', $oauth['facebook_client_id']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">App Secret</label>
                            <input type="password" name="oauth[facebook_client_secret]" value="{{ old('oauth.facebook_client_secret', $oauth['facebook_client_secret']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Redirect URI</label>
                            <input type="url" name="oauth[facebook_redirect_uri]" value="{{ old('oauth.facebook_redirect_uri', $oauth['facebook_redirect_uri']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" placeholder="https://gate.sabira-iibs.id/auth/facebook/callback">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-primary">mail</span>
                <div>
                    <h2 class="text-lg font-bold">Email (Outgoing Mail)</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Konfigurasi SMTP untuk email reset password dan notifikasi.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Mailer</label>
                    <select name="email[mailer]" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                        @foreach(['smtp', 'log', 'sendmail', 'array'] as $mailer)
                            <option value="{{ $mailer }}" {{ $email['mailer'] === $mailer ? 'selected' : '' }}>{{ strtoupper($mailer) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Host</label>
                    <input type="text" name="email[host]" value="{{ old('email.host', $email['host']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Port</label>
                    <input type="number" name="email[port]" value="{{ old('email.port', $email['port']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Scheme</label>
                    <input type="text" name="email[scheme]" value="{{ old('email.scheme', $email['scheme']) }}" placeholder="smtp / smtps (ssl)" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Username</label>
                    <input type="text" name="email[username]" value="{{ old('email.username', $email['username']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Password</label>
                    <input type="password" name="email[password]" value="{{ old('email.password', $email['password']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">From Address</label>
                    <input type="email" name="email[from_address]" value="{{ old('email.from_address', $email['from_address']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">From Name</label>
                    <input type="text" name="email[from_name]" value="{{ old('email.from_name', $email['from_name']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Reply To</label>
                    <input type="email" name="email[reply_to]" value="{{ old('email.reply_to', $email['reply_to']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                </div>
            </div>

            <div class="mt-6 flex flex-col md:flex-row md:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Test Send Email</label>
                    <input type="email" name="email[test_address]" value="{{ old('email.test_address') }}" placeholder="admin@sabira-iibs.id" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
                    @error('email.test_address')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" name="action" value="test-email" class="px-4 py-2.5 rounded-lg border border-primary text-primary font-semibold hover:bg-primary hover:text-white transition-colors">
                    Test Send
                </button>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-primary">api</span>
                <div>
                    <h2 class="text-lg font-bold">Web Services</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Informasi endpoint OIDC dan dokumentasi API publik.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="grid gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">API Documentation URL</label>
                        <input type="url" name="web[api_docs_url]" value="{{ old('web.api_docs_url', $web['api_docs_url']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" placeholder="https://docs.sabira-iibs.id">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Postman Collection URL</label>
                        <input type="url" name="web[postman_url]" value="{{ old('web.postman_url', $web['postman_url']) }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" placeholder="https://.../postman.json">
                    </div>
                </div>
                <div class="rounded-lg border border-slate-100 dark:border-slate-800 p-4">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-3">OIDC Endpoints</h3>
                    <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <div><span class="font-medium text-slate-700 dark:text-slate-200">Issuer:</span> {{ $endpoints['issuer'] }}</div>
                        <div><span class="font-medium text-slate-700 dark:text-slate-200">Authorize:</span> {{ $endpoints['authorization'] }}</div>
                        <div><span class="font-medium text-slate-700 dark:text-slate-200">Token:</span> {{ $endpoints['token'] }}</div>
                        <div><span class="font-medium text-slate-700 dark:text-slate-200">Userinfo:</span> {{ $endpoints['userinfo'] }}</div>
                        <div><span class="font-medium text-slate-700 dark:text-slate-200">Discovery:</span> {{ $endpoints['discovery'] }}</div>
                        <div><span class="font-medium text-slate-700 dark:text-slate-200">JWKS:</span> {{ $endpoints['jwks'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-primary text-white font-semibold hover:bg-blue-700 transition-colors">
                Simpan Pengaturan Server
            </button>
        </div>
    </form>
</div>
@endsection
