@extends('layouts.admin')

@section('page-title', 'Detail User')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    @if (session('status'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Main User Details Card --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-24 h-18 bg-white/20 rounded-lg overflow-hidden flex items-center justify-center flex-shrink-0 border border-white/30">
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                        <p class="text-blue-200">{{ '@' . $user->username }}</p>
                        <p class="text-xs text-blue-300 font-mono mt-1">UUID: {{ $user->uuid }}</p>
                    </div>
                </div>
                @if(auth()->user()?->hasRole('superadmin') || ! $user->hasRole('superadmin'))
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">edit</span> Edit
                        </a>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div><span class="text-gray-500 text-sm">Email</span><p class="font-medium">{{ $user->email ?? '-' }}</p></div>
                <div><span class="text-gray-500 text-sm">Tipe</span><p class="font-medium capitalize">{{ $user->type }}</p></div>
                <div><span class="text-gray-500 text-sm">NIS</span><p class="font-medium">{{ $user->nis ?? '-' }}</p></div>
                <div><span class="text-gray-500 text-sm">NIP</span><p class="font-medium">{{ $user->nip ?? '-' }}</p></div>
                <div>
                    <span class="text-gray-500 text-sm">Kode QR Kartu</span>
                    <p class="font-medium">
                        @if($user->qr_code)
                            <span class="inline-flex items-center gap-1 font-mono text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                <span class="material-symbols-outlined text-[16px] text-emerald-600">qr_code_2</span>
                                {{ $user->qr_code }}
                            </span>
                        @else
                            <span class="text-slate-400 font-normal">Belum terdaftar</span>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-gray-500 text-sm">Status Utama</span>
                    <p>
                        @if($user->status === 'active')<span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Active</span>
                        @elseif($user->status === 'suspended')<span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Suspended</span>
                        @else<span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-full">Pending</span>@endif
                    </p>
                </div>
                <div>
                    <span class="text-gray-500 text-sm">Roles Gate</span>
                    <p>@foreach($user->roles as $role)<span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full mr-1">{{ $role->name }}</span>@endforeach</p>
                </div>
                <div><span class="text-gray-500 text-sm">Last Login</span><p class="font-medium">{{ $user->last_login_at?->diffForHumans() ?? '-' }}</p></div>
            </div>
        </div>
    </div>

    {{-- Application Access Management Section --}}
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">apps</span>
                    Application Access (Hak Akses Aplikasi)
                </h3>
                <p class="text-sm text-gray-500">Kelola izin akses user ini ke aplikasi ekosistem Sabira Connect.</p>
            </div>
            <button onclick="document.getElementById('modal-grant-access').classList.remove('hidden')"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium flex items-center gap-1">
                <span class="material-symbols-outlined text-[18px]">add</span> Beri Akses Aplikasi
            </button>
        </div>

        <div class="overflow-x-auto border rounded-lg">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-slate-50 text-gray-700 text-xs uppercase border-b">
                    <tr>
                        <th class="px-4 py-3">Aplikasi</th>
                        <th class="px-4 py-3">Application Role</th>
                        <th class="px-4 py-3">Access Status</th>
                        <th class="px-4 py-3">Sync Status</th>
                        <th class="px-4 py-3">Pemberi / Pencabut</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($applicationAccesses as $item)
                        @php
                            $app = $item['application'];
                            $access = $item['access'];
                            $sync = $item['sync_status'];
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 font-medium text-gray-900 flex items-center gap-2">
                                @if($app->logo_url)
                                    <img src="{{ $app->logo_url }}" alt="{{ $app->name }}" class="w-7 h-7 rounded object-cover border">
                                @else
                                    <div class="w-7 h-7 bg-blue-100 text-blue-700 font-bold rounded flex items-center justify-center text-xs">
                                        {{ strtoupper(substr($app->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-semibold">{{ $app->name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $app->slug }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($access?->application_role)
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-xs rounded border font-mono">
                                        {{ $access->application_role }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($access?->status === 'active')
                                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full">Active</span>
                                @elseif($access?->status === 'inactive')
                                    <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full">Inactive</span>
                                @elseif($access?->status === 'revoked')
                                    <span class="px-2.5 py-0.5 bg-rose-100 text-rose-800 text-xs font-semibold rounded-full">Revoked</span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 text-xs font-semibold rounded-full">No Access</span>
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
                                    @if($sync->last_sync_at)
                                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $sync->last_sync_at->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-xs">Never Synced</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                @if($access?->granted_at)
                                    <div>Pemberi: <span class="font-medium text-gray-700">{{ $access->grantedBy?->name ?? 'System' }}</span> ({{ $access->granted_at->format('d/m/Y') }})</div>
                                @endif
                                @if($access?->revoked_at)
                                    <div class="text-rose-600">Pencabut: {{ $access->revokedBy?->name ?? 'System' }} ({{ $access->revoked_at->format('d/m/Y') }})</div>
                                @endif
                                @if(!$access?->granted_at && !$access?->revoked_at)
                                    <span>—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-1">
                                @if($access)
                                    <button onclick="editAccessModal('{{ $app->id }}', '{{ $app->name }}', '{{ $access->application_role }}', '{{ $access->status }}')"
                                        class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs rounded border transition">
                                        Edit
                                    </button>
                                    @if($access->status !== 'revoked')
                                        <form action="{{ route('admin.users.applications.destroy', [$user, $app]) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Yakin ingin mencabut akses user dari aplikasi {{ $app->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs rounded border border-rose-200 transition">
                                                Cabut
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <button onclick="grantAccessForApp('{{ $app->id }}', '{{ $app->name }}')"
                                        class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs rounded border border-blue-200 font-medium transition">
                                        Beri Akses
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400">Belum ada aplikasi yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Login Logs Section --}}
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="font-semibold text-gray-800 mb-3">Log Login Terbaru</h3>
        <div class="bg-gray-50 rounded-lg p-4">
            @forelse($user->loginLogs->take(5) as $log)
            <div class="flex justify-between py-2 {{ !$loop->last ? 'border-b' : '' }}">
                <span class="text-gray-600">{{ $log->login_at->format('d M Y H:i') }} - {{ $log->client_app }}</span>
                <span class="text-gray-400 text-sm">{{ $log->ip_address }}</span>
            </div>
            @empty
            <p class="text-gray-500 text-sm">Belum ada log login.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- Modal Beri Akses Aplikasi --}}
<div id="modal-grant-access" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Beri Akses Aplikasi Baru</h3>
        <form action="{{ route('admin.users.applications.store', $user) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Aplikasi</label>
                <select name="application_id" id="grant_app_id" required class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Pilih Aplikasi --</option>
                    @foreach($availableApplications as $appOption)
                        <option value="{{ $appOption->id }}">{{ $appOption->name }} ({{ $appOption->slug }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Application Role (Opsional)</label>
                <input type="text" name="application_role" placeholder="Misal: santri, guru, student, admin"
                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Role khusus yang akan dikirim ke aplikasi tujuan saat sinkronisasi.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status Akses</label>
                <select name="status" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="active">Active (Akses Diberikan & Aktif)</option>
                    <option value="inactive">Inactive (Non-aktifkan Sementara)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-grant-access').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">Simpan Akses</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit Akses --}}
<div id="modal-edit-access" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Edit Hak Akses Aplikasi</h3>
        <p id="edit-app-name" class="text-xs text-blue-600 font-medium mb-4"></p>
        <form id="form-edit-access" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Application Role</label>
                <input type="text" name="application_role" id="edit_application_role" placeholder="Misal: santri, guru, admin"
                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status Akses</label>
                <select name="status" id="edit_status" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="active">Active (Akses Aktif)</option>
                    <option value="inactive">Inactive (Non-aktifkan Akses)</option>
                    <option value="revoked">Revoked (Cabut Akses Resmi)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-edit-access').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function grantAccessForApp(appId, appName) {
        document.getElementById('grant_app_id').value = appId;
        document.getElementById('modal-grant-access').classList.remove('hidden');
    }

    function editAccessModal(appId, appName, currentRole, currentStatus) {
        document.getElementById('edit-app-name').innerText = appName;
        document.getElementById('edit_application_role').value = currentRole || '';
        document.getElementById('edit_status').value = currentStatus || 'active';
        
        const form = document.getElementById('form-edit-access');
        form.action = `/admin/users/{{ $user->id }}/applications/${appId}`;
        
        document.getElementById('modal-edit-access').classList.remove('hidden');
    }
</script>
@endsection
