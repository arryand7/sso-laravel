@extends('layouts.admin')
@section('page-title', 'Detail Aplikasi')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center"><span class="material-symbols-outlined text-3xl">{{ $application->icon ?? 'apps' }}</span></div>
                    <div><h1 class="text-2xl font-bold">{{ $application->name }}</h1><p class="text-blue-200">{{ $application->base_url }}</p></div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.applications.users', $application) }}" class="px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">group</span> Lihat Users
        </a>
        <a href="{{ route('admin.applications.edit', $application) }}" class="px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">edit</span> Edit
        </a>
    </div>
</div>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-6">
                <div><span class="text-gray-500 text-sm">Slug</span><p class="font-medium"><code class="bg-gray-100 px-2 py-1 rounded">{{ $application->slug }}</code></p></div>
                <div><span class="text-gray-500 text-sm">Kategori</span><p class="font-medium">{{ $application->category ?? '-' }}</p></div>
                <div><span class="text-gray-500 text-sm">Status</span><p>@if($application->is_active)<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>@else<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Inactive</span>@endif</p></div>
                <div class="col-span-2"><span class="text-gray-500 text-sm">Redirect URI</span><p class="font-medium break-all">{{ $application->redirect_uri }}</p></div>
                <div class="col-span-2"><span class="text-gray-500 text-sm">SSO Login URL</span><p class="font-medium break-all">{{ $application->sso_login_url ?? '-' }}</p></div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Client Credentials -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">OAuth Credentials</h3>
                    <div class="bg-gray-50 p-4 rounded-lg border space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Client ID</label>
                            <div class="flex items-center mt-1">
                                <code class="bg-white px-2 py-1 rounded border text-sm font-mono flex-1">{{ $application->client_id }}</code>
                                <button class="ml-2 text-gray-400 hover:text-blue-600" onclick="navigator.clipboard.writeText('{{ $application->client_id }}')"><span class="material-symbols-outlined text-[20px]">content_copy</span></button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Client Secret</label>
                            <div class="flex flex-col gap-2 mt-1">
                                <div class="flex items-center gap-2">
                                    <code id="client-secret-value" data-secret="{{ $application->client_secret }}" class="bg-white px-2 py-1 rounded border text-sm font-mono flex-1">********************************</code>
                                    <button id="client-secret-toggle" type="button" class="text-gray-500 hover:text-blue-600 text-sm">
                                        Tampilkan
                                    </button>
                                    <button id="client-secret-copy" type="button" class="text-gray-500 hover:text-blue-600 text-sm">
                                        Copy
                                    </button>
                                </div>
                                <div>
                                    <form method="POST" action="{{ route('admin.applications.regenerate-secret', $application) }}" onsubmit="return confirm('Regenerate secret? Client lama akan tidak bisa login.')">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-800 text-sm flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px]">sync</span> Regenerate
                                        </button>
                                    </form>
                                </div>
                                <p class="text-xs text-gray-500">Klik "Tampilkan" untuk melihat secret dan "Copy" untuk menyalin.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const secretEl = document.getElementById('client-secret-value');
                    const toggleBtn = document.getElementById('client-secret-toggle');
                    const copyBtn = document.getElementById('client-secret-copy');
                    if (!secretEl || !toggleBtn || !copyBtn) {
                        return;
                    }

                    const masked = '********************************';
                    const secret = secretEl.dataset.secret || '';
                    let revealed = false;

                    const updateView = () => {
                        secretEl.textContent = revealed ? secret : masked;
                        toggleBtn.textContent = revealed ? 'Sembunyikan' : 'Tampilkan';
                    };

                    toggleBtn.addEventListener('click', () => {
                        revealed = !revealed;
                        updateView();
                    });

                    copyBtn.addEventListener('click', () => {
                        if (!secret) {
                            return;
                        }
                        navigator.clipboard.writeText(secret);
                        copyBtn.textContent = 'Tersalin';
                        setTimeout(() => {
                            copyBtn.textContent = 'Copy';
                        }, 1200);
                    });

                    updateView();
                });
            </script>
        <div class="border-t pt-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Roles dengan Akses</h3>
                <div class="flex flex-wrap gap-2">@foreach($application->roles as $role)<span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full">{{ $role->name }}</span>@endforeach</div>
            </div>
            
            <div class="border-t pt-6">
                <h3 class="font-semibold text-gray-800 mb-3">Users dengan Akses ({{ $users->total() }} total)</h3>
                <div class="bg-gray-50 rounded-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-100"><tr><th class="px-4 py-2 text-left text-xs text-gray-500">Nama</th><th class="px-4 py-2 text-left text-xs text-gray-500">Username</th><th class="px-4 py-2 text-left text-xs text-gray-500">Tipe</th></tr></thead>
                        <tbody class="divide-y">@forelse($users->take(10) as $user)<tr><td class="px-4 py-2">{{ $user->name }}</td><td class="px-4 py-2">{{ $user->username }}</td><td class="px-4 py-2 capitalize">{{ $user->type }}</td></tr>@empty<tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">Tidak ada user.</td></tr>@endforelse</tbody>
                    </table>
                </div>
                @if($users->total() > 10)<a href="{{ route('admin.applications.users', $application) }}" class="inline-block mt-3 text-blue-600 hover:text-blue-800 text-sm">Lihat semua {{ $users->total() }} users →</a>@endif
            </div>
        </div>
    </div>
</div>
@endsection
