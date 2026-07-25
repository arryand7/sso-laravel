@extends('layouts.admin')

@section('page-title', 'Detail Aplikasi')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    @if (session('status'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Main Application Info Card --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center overflow-hidden border border-white/30">
                        @if($application->logo_url)
                            <img src="{{ $application->logo_url }}" alt="Logo {{ $application->name }}" class="h-full w-full object-cover">
                        @else
                            <span class="material-symbols-outlined text-3xl">{{ $application->icon ?? 'apps' }}</span>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">{{ $application->name }}</h1>
                        <p class="text-blue-200">{{ $application->base_url }}</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.applications.edit', $application) }}" class="px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">edit</span> Edit Aplikasi
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div><span class="text-gray-500 text-sm">Slug</span><p class="font-medium"><code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $application->slug }}</code></p></div>
                <div><span class="text-gray-500 text-sm">Kategori</span><p class="font-medium">{{ $application->category ?? '-' }}</p></div>
                <div>
                    <span class="text-gray-500 text-sm">Status Portal</span>
                    <p>
                        @if($application->is_active)
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Active</span>
                        @else
                            <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Inactive</span>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-gray-500 text-sm">Sync API Status</span>
                    <p>
                        @if($application->sync_enabled)
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full">Sync Enabled</span>
                        @else
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full">Sync Disabled</span>
                        @endif
                    </p>
                </div>
                <div class="col-span-2"><span class="text-gray-500 text-sm">Redirect URI</span><p class="font-medium break-all text-xs font-mono bg-slate-50 p-2 rounded border">{{ $application->redirect_uri }}</p></div>
                <div class="col-span-2"><span class="text-gray-500 text-sm">SSO Login URL</span><p class="font-medium break-all text-xs font-mono bg-slate-50 p-2 rounded border">{{ $application->sso_login_url ?? '-' }}</p></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- OAuth Credentials --}}
                <div class="bg-slate-50 rounded-lg p-5 border space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600 text-[18px]">key</span> OAuth & Provisioning API Credentials
                    </h3>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Client ID</label>
                        <div class="flex items-center mt-1 gap-2">
                            <code class="bg-white px-2 py-1.5 rounded border text-xs font-mono flex-1 text-slate-800">{{ $application->client_id }}</code>
                            <button class="px-2 py-1 bg-white border hover:bg-slate-100 text-slate-600 rounded text-xs" onclick="navigator.clipboard.writeText('{{ $application->client_id }}')">
                                Copy
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Client Secret</label>
                        <div class="flex flex-col gap-2 mt-1">
                            <div class="flex items-center gap-2">
                                <code id="client-secret-value" data-secret="{{ $application->client_secret }}" class="bg-white px-2 py-1.5 rounded border text-xs font-mono flex-1 text-slate-800">********************************</code>
                                <button id="client-secret-toggle" type="button" class="px-2 py-1 bg-white border hover:bg-slate-100 text-slate-600 rounded text-xs">
                                    Tampilkan
                                </button>
                                <button id="client-secret-copy" type="button" class="px-2 py-1 bg-white border hover:bg-slate-100 text-slate-600 rounded text-xs">
                                    Copy
                                </button>
                            </div>
                            <div>
                                <form method="POST" action="{{ route('admin.applications.regenerate-secret', $application) }}" onsubmit="return confirm('Regenerate secret? Client lama akan tidak bisa login.')">
                                    @csrf
                                    <button type="submit" class="text-orange-600 hover:text-orange-800 text-xs font-medium flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">sync</span> Regenerate Secret
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Provisioning Capabilities Settings --}}
                <div class="bg-slate-50 rounded-lg p-5 border">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600 text-[18px]">tune</span> Sync Capabilities & Field Config
                    </h3>
                    <form action="{{ route('admin.applications.capabilities.update', $application) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="flex items-center justify-between pb-2 border-b">
                            <span class="text-xs font-medium text-gray-700">Fitur Sync API Status</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="sync_enabled" value="1" {{ $application->sync_enabled ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            @php $caps = $application->getEffectiveCapabilities(); @endphp
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="capabilities[sync_photo]" value="1" {{ !empty($caps['sync_photo']) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600">
                                <span>Kirim Foto Profile</span>
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="capabilities[sync_qr]" value="1" {{ !empty($caps['sync_qr']) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600">
                                <span>Kirim Kode QR Kartu</span>
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="capabilities[sync_role]" value="1" {{ !empty($caps['sync_role']) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600">
                                <span>Kirim Application Role</span>
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="capabilities[create_user]" value="1" {{ !empty($caps['create_user']) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600">
                                <span>Izin Buat User Lokal</span>
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="capabilities[update_user]" value="1" {{ !empty($caps['update_user']) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600">
                                <span>Izin Update User Lokal</span>
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="capabilities[suspend_user]" value="1" {{ !empty($caps['suspend_user']) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600">
                                <span>Izin Suspend User</span>
                            </label>
                        </div>
                        <div class="pt-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">Rate Limit:</span>
                                <input type="number" name="api_rate_limit" value="{{ $application->api_rate_limit ?? 60 }}" class="w-16 px-2 py-1 text-xs border rounded">
                                <span class="text-[11px] text-gray-400">req/min</span>
                            </div>
                            <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded">
                                Simpan Config
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Users with Access Section --}}
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div>
                <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">group</span>
                    Users with Access (Daftar Hak Akses User)
                </h3>
                <p class="text-sm text-gray-500">Seluruh user yang telah diberikan hak akses ke aplikasi {{ $application->name }}.</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="document.getElementById('modal-bulk-grant').classList.remove('hidden')"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg flex items-center gap-1">
                    <span class="material-symbols-outlined text-[18px]">group_add</span> Tambah Akses Banyak User
                </button>
                <button onclick="document.getElementById('modal-grant-single').classList.remove('hidden')"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg flex items-center gap-1">
                    <span class="material-symbols-outlined text-[18px]">person_add</span> Tambah Akses User
                </button>
            </div>
        </div>

        {{-- Filter Bar --}}
        <form action="{{ route('admin.applications.show', $application) }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 mb-4 bg-slate-50 p-3 rounded-lg border">
            <div>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama / NIS / NIP..."
                    class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <select name="type" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Semua Tipe User --</option>
                    <option value="student" {{ ($filters['type'] ?? '') === 'student' ? 'selected' : '' }}>Student</option>
                    <option value="teacher" {{ ($filters['type'] ?? '') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                    <option value="parent" {{ ($filters['type'] ?? '') === 'parent' ? 'selected' : '' }}>Parent</option>
                    <option value="staff" {{ ($filters['type'] ?? '') === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="admin" {{ ($filters['type'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            <div>
                <select name="status" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Status Akses --</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="revoked" {{ ($filters['status'] ?? '') === 'revoked' ? 'selected' : '' }}>Revoked</option>
                </select>
            </div>
            <div>
                <select name="sync_status" class="w-full border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Status Sync --</option>
                    <option value="matched" {{ ($filters['sync_status'] ?? '') === 'matched' ? 'selected' : '' }}>Matched</option>
                    <option value="needs_update" {{ ($filters['sync_status'] ?? '') === 'needs_update' ? 'selected' : '' }}>Needs Update</option>
                    <option value="missing_in_application" {{ ($filters['sync_status'] ?? '') === 'missing_in_application' ? 'selected' : '' }}>Missing in App</option>
                    <option value="suspended" {{ ($filters['sync_status'] ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="never_synced" {{ ($filters['sync_status'] ?? '') === 'never_synced' ? 'selected' : '' }}>Never Synced</option>
                </select>
            </div>
            <div class="flex items-center gap-1">
                <button type="submit" class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">filter_alt</span> Filter
                </button>
                <a href="{{ route('admin.applications.show', $application) }}" class="p-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg flex items-center">
                    <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                </a>
            </div>
        </form>

        {{-- Access Table --}}
        <div class="overflow-x-auto border rounded-lg">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-slate-50 text-gray-700 text-xs uppercase border-b">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">NIS / NIP / Username</th>
                        <th class="px-4 py-3">Tipe User</th>
                        <th class="px-4 py-3">App Role</th>
                        <th class="px-4 py-3">Access Status</th>
                        <th class="px-4 py-3">Sync Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($userAccesses as $access)
                        @php
                            $usr = $access->user;
                            $sync = $access->syncStatus;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 font-medium text-gray-900 flex items-center gap-3">
                                <img src="{{ $usr->avatar_url }}" alt="{{ $usr->name }}" class="w-8 h-8 rounded-full object-cover border">
                                <div>
                                    <a href="{{ route('admin.users.show', $usr) }}" class="font-semibold hover:text-blue-600">{{ $usr->name }}</a>
                                    <div class="text-xs text-gray-400 font-mono">{{ $usr->email ?? '@'.$usr->username }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">
                                {{ $usr->nis ?? $usr->nip ?? $usr->username }}
                            </td>
                            <td class="px-4 py-3 capitalize text-xs">
                                {{ $usr->type }}
                            </td>
                            <td class="px-4 py-3">
                                @if($access->application_role)
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-xs rounded border font-mono">
                                        {{ $access->application_role }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($access->status === 'active')
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full">Active</span>
                                @elseif($access->status === 'inactive')
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full">Inactive</span>
                                @else
                                    <span class="px-2 py-0.5 bg-rose-100 text-rose-800 text-xs font-semibold rounded-full">Revoked</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($sync)
                                    @php
                                        $syncBadgeClass = match($sync->status) {
                                            'matched' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'needs_update' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'missing_in_application' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'suspended' => 'bg-orange-50 text-orange-700 border-orange-200',
                                            'conflict' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'failed' => 'bg-red-50 text-red-700 border-red-200',
                                            default => 'bg-slate-50 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs border {{ $syncBadgeClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ $sync->status_label }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">Never Synced</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-1">
                                <button onclick="editUserAppAccess('{{ $usr->id }}', '{{ $usr->name }}', '{{ $access->application_role }}', '{{ $access->status }}')"
                                    class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs rounded border transition">
                                    Edit
                                </button>
                                @if($access->status !== 'revoked')
                                    <form action="{{ route('admin.applications.users.destroy', [$application, $usr]) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Yakin ingin mencabut akses user {{ $usr->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs rounded border border-rose-200 transition">
                                            Cabut
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Tidak ada user yang ditemukan dengan filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $userAccesses->links() }}
        </div>
    </div>

</div>

{{-- Modal Grant Single User Access --}}
<div id="modal-grant-single" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Beri Akses User ke {{ $application->name }}</h3>
        <form action="{{ route('admin.applications.users.grant', $application) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih User</label>
                <select name="user_id" required class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Pilih User --</option>
                    @foreach($allUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->username }} - {{ $u->type }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Application Role (Opsional)</label>
                <input type="text" name="application_role" placeholder="Misal: santri, guru, student"
                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status Akses</label>
                <select name="status" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="active">Active (Akses Aktif)</option>
                    <option value="inactive">Inactive (Non-aktifkan Sementara)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-grant-single').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">Simpan Akses</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Bulk Grant Access --}}
<div id="modal-bulk-grant" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 relative">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Beri Akses ke Banyak User Sekaligus</h3>
        <p class="text-xs text-gray-500 mb-4">Pilih user-user yang ingin diberikan hak akses ke aplikasi {{ $application->name }}.</p>
        <form action="{{ route('admin.applications.users.bulk-grant', $application) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Application Role (Opsional)</label>
                <input type="text" name="application_role" placeholder="Misal: santri, guru, student"
                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Daftar User (Pilih Banyak)</label>
                <div class="max-h-56 overflow-y-auto border rounded-lg p-2 space-y-1 bg-slate-50">
                    @foreach($allUsers as $u)
                        <label class="flex items-center gap-2 p-1.5 hover:bg-white rounded cursor-pointer text-xs">
                            <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="rounded text-blue-600">
                            <span class="font-medium text-gray-800">{{ $u->name }}</span>
                            <span class="text-gray-400 font-mono text-[11px]">({{ $u->username }} - {{ $u->type }})</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-bulk-grant').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">Berikan Akses Massal</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit User App Access --}}
<div id="modal-edit-user-access" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Edit Hak Akses User</h3>
        <p id="edit-user-name" class="text-xs text-blue-600 font-medium mb-4"></p>
        <form id="form-edit-user-access" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Application Role</label>
                <input type="text" name="application_role" id="app_user_role" placeholder="Misal: santri, guru, admin"
                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status Akses</label>
                <select name="status" id="app_user_status" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="active">Active (Akses Aktif)</option>
                    <option value="inactive">Inactive (Non-aktifkan Akses)</option>
                    <option value="revoked">Revoked (Cabut Akses Resmi)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-edit-user-access').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const secretEl = document.getElementById('client-secret-value');
        const toggleBtn = document.getElementById('client-secret-toggle');
        const copyBtn = document.getElementById('client-secret-copy');
        if (secretEl && toggleBtn && copyBtn) {
            const masked = '********************************';
            const secret = secretEl.dataset.secret || '';
            let revealed = false;

            toggleBtn.addEventListener('click', () => {
                revealed = !revealed;
                secretEl.textContent = revealed ? secret : masked;
                toggleBtn.textContent = revealed ? 'Sembunyikan' : 'Tampilkan';
            });

            copyBtn.addEventListener('click', () => {
                if (secret) {
                    navigator.clipboard.writeText(secret);
                    copyBtn.textContent = 'Tersalin';
                    setTimeout(() => { copyBtn.textContent = 'Copy'; }, 1200);
                }
            });
        }
    });

    function editUserAppAccess(userId, userName, currentRole, currentStatus) {
        document.getElementById('edit-user-name').innerText = userName;
        document.getElementById('app_user_role').value = currentRole || '';
        document.getElementById('app_user_status').value = currentStatus || 'active';
        
        const form = document.getElementById('form-edit-user-access');
        form.action = `/admin/applications/{{ $application->id }}/users/${userId}`;
        
        document.getElementById('modal-edit-user-access').classList.remove('hidden');
    }
</script>
@endsection
