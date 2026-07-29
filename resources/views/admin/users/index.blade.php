@extends('layouts.admin')

@section('page-title', 'Manajemen User')

@section('content')
@if (isset($activePhotoImportBatch) && $activePhotoImportBatch)
    <div class="mb-4 p-4 bg-purple-50 border border-purple-200 rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-3 text-purple-900">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-purple-600 text-[24px] {{ $activePhotoImportBatch->status === 'importing' ? 'animate-spin' : '' }}">sync</span>
            <div>
                <div class="font-bold text-sm">
                    Batch Import Foto User #{{ $activePhotoImportBatch->id }} — {{ $activePhotoImportBatch->status_label }}
                </div>
                <div class="text-xs text-purple-700 mt-0.5">
                    File: <strong>{{ $activePhotoImportBatch->original_filename }}</strong> | 
                    Progress: <strong>{{ $activePhotoImportBatch->processed_count }} / {{ $activePhotoImportBatch->ready_new_count + $activePhotoImportBatch->ready_replace_count }} foto diproses</strong>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.users.photo-import.show', $activePhotoImportBatch) }}" 
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs rounded-lg transition-colors flex-shrink-0">
            <span class="material-symbols-outlined text-[18px]">visibility</span>
            Lihat Progress & Detail Batch
        </a>
    </div>
@endif

<div class="flex flex-wrap justify-between items-center gap-4 mb-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Daftar User</h2>
        <p class="text-gray-600">Kelola semua user di sistem Sabira Connect</p>
    </div>
    <div class="flex flex-wrap items-center justify-end gap-3">
        <div class="relative js-bulk-menu-wrap hidden">
            <button type="button" class="js-bulk-menu-button px-4 py-2 border border-slate-300 bg-white text-slate-700 rounded-lg hover:bg-slate-50 flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">checklist</span>
                Aksi Massal
                <span class="js-selected-count inline-flex min-w-5 justify-center rounded-full bg-blue-600 px-1.5 text-xs font-semibold text-white">0</span>
                <span class="material-symbols-outlined text-[18px]">expand_more</span>
            </button>
            <div class="js-bulk-menu hidden absolute right-0 z-20 mt-2 w-[min(92vw,420px)] rounded-lg border border-slate-200 bg-white p-4 shadow-xl">
                <form method="POST" action="{{ route('admin.users.bulk-actions') }}" id="bulk-action-form" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-[140px_1fr] gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Aksi</label>
                            <select name="action" required class="js-bulk-action w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                                <option value="roles_add">Tambah role</option>
                                <option value="roles_replace">Ganti role</option>
                                <option value="type_change">Ubah tipe user</option>
                                @if(auth()->user()?->hasRole('superadmin'))
                                    <option value="deactivate_selected">Nonaktifkan user terpilih</option>
                                    <option value="delete_selected">Hapus user terpilih</option>
                                @endif
                            </select>
                        </div>
                        <div class="js-bulk-type-field hidden">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Tipe User</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                                <option value="parent">Parent</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="js-bulk-role-fields sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-2">Roles</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($roles as $role)
                                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-sm text-gray-700 hover:bg-slate-50">
                                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded border-gray-300 text-blue-600">
                                        {{ $role->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <p class="text-xs text-slate-500"><span class="js-selected-count-text">0 user</span> dipilih</p>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">done_all</span>
                            Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <a href="{{ route('admin.users.import.template') }}" class="px-4 py-2 border border-blue-200 text-blue-700 bg-blue-50/50 rounded-lg hover:bg-blue-100/60 flex items-center gap-2 transition-colors">
            <span class="material-symbols-outlined text-[20px]">download</span> Download Template
        </a>
        <a href="{{ route('admin.users.import') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">upload_file</span> Import Excel
        </a>
        <a href="{{ route('admin.users.photo-import.index') }}" class="px-4 py-2 border border-purple-200 text-purple-700 bg-purple-50/50 rounded-lg hover:bg-purple-100/60 flex items-center gap-2 transition-colors">
            <span class="material-symbols-outlined text-[20px]">folder_zip</span> Import Foto ZIP
        </a>
        <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">add</span> Tambah User
        </a>
    </div>
</div>

@php
    $currentSort = $sort ?? request('sort', 'name');
    $currentDirection = $direction ?? request('direction', 'asc');
    $nextDirection = function (string $field) use ($currentSort, $currentDirection) {
        return $currentSort === $field && $currentDirection === 'asc' ? 'desc' : 'asc';
    };
    $sortArrow = function (string $field) use ($currentSort, $currentDirection) {
        if ($currentSort !== $field) {
            return '';
        }
        return $currentDirection === 'asc' ? '▲' : '▼';
    };
@endphp

<!-- Users Table -->
<div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
    <div class="border-b border-slate-100 dark:border-slate-800 p-4">
        <form method="GET" class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(240px,1fr)_140px_150px_150px_120px_auto_auto] lg:items-end">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Nama, username, email, NIS, NIP, QR..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Tipe</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                    <option value="">Semua</option>
                    <option value="student" {{ request('type') === 'student' ? 'selected' : '' }}>Student</option>
                    <option value="teacher" {{ request('type') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                    <option value="parent" {{ request('type') === 'parent' ? 'selected' : '' }}>Parent</option>
                    <option value="staff" {{ request('type') === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="admin" {{ request('type') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                    <option value="">Semua</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Role</label>
                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                    <option value="">Semua</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Tampilkan</label>
                <select name="per_page" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                    @foreach([10, 15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', $perPage ?? 15) === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[20px]">search</span> Filter
            </button>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-center text-gray-600 hover:text-gray-900">Reset</a>
        </form>
    </div>
    <div class="p-4">
<x-admin.table
    :ordering="false"
    :dtPaging="false"
    :dtInfo="false"
    :dtSearch="false"
    :dtLengthChange="false"
    :framed="false"
    dtDom="<'row mb-3 align-items-center'<'col-sm-12 d-flex align-items-center justify-end gap-2'B>>t">
    <x-slot:head>
        <tr>
            <th class="px-4 py-3 text-left">
                <input type="checkbox" class="js-select-all rounded border-gray-300 text-blue-600">
            </th>
            <th class="px-4 py-3 text-left">
                <a href="{{ route('admin.users.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => $nextDirection('name')])) }}" class="inline-flex items-center gap-1">
                    User <span class="text-xs text-slate-400">{{ $sortArrow('name') }}</span>
                </a>
            </th>
            <th class="px-4 py-3 text-left">
                <a href="{{ route('admin.users.index', array_merge(request()->all(), ['sort' => 'username', 'direction' => $nextDirection('username')])) }}" class="inline-flex items-center gap-1">
                    Username <span class="text-xs text-slate-400">{{ $sortArrow('username') }}</span>
                </a>
            </th>
            <th class="px-4 py-3 text-left">
                <a href="{{ route('admin.users.index', array_merge(request()->all(), ['sort' => 'type', 'direction' => $nextDirection('type')])) }}" class="inline-flex items-center gap-1">
                    Tipe <span class="text-xs text-slate-400">{{ $sortArrow('type') }}</span>
                </a>
            </th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">Kartu QR</th>
            <th class="px-4 py-3 text-left">
                <a href="{{ route('admin.users.index', array_merge(request()->all(), ['sort' => 'status', 'direction' => $nextDirection('status')])) }}" class="inline-flex items-center gap-1">
                    Status <span class="text-xs text-slate-400">{{ $sortArrow('status') }}</span>
                </a>
            </th>
            <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
    </x-slot:head>
    <x-slot:body>
        @forelse($users as $user)
            @php
                $canManageRow = auth()->user()?->hasRole('superadmin') || ! $user->hasRole('superadmin');
            @endphp
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3.5">
                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" form="bulk-action-form" class="js-user-checkbox rounded border-gray-300 text-blue-600 disabled:opacity-40" {{ $canManageRow ? '' : 'disabled' }}>
                </td>
                <td class="px-4 py-3.5">
                    <div class="flex items-center gap-3">
                        <div class="relative w-10 h-7.5 bg-slate-100 dark:bg-slate-800 rounded overflow-hidden border border-slate-200 dark:border-slate-700 flex-shrink-0 flex items-center justify-center">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email ?? '-' }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3.5">
                    <span class="text-slate-900 dark:text-slate-100">{{ $user->username }}</span>
                    @if($user->nis)
                        <div class="text-xs text-slate-500 dark:text-slate-400">NIS: {{ $user->nis }}</div>
                    @elseif($user->nip)
                        <div class="text-xs text-slate-500 dark:text-slate-400">NIP: {{ $user->nip }}</div>
                    @endif
                </td>
                <td class="px-4 py-3.5">
                    <span class="capitalize text-slate-700 dark:text-slate-300">{{ $user->type }}</span>
                </td>
                <td class="px-4 py-3.5">
                    @foreach($user->roles as $role)
                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">{{ $role->name }}</span>
                    @endforeach
                </td>
                <td class="px-4 py-3.5">
                    @if($user->qr_code)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 text-xs font-medium rounded-full border border-emerald-200/60 dark:border-emerald-800/40">
                            <span class="material-symbols-outlined text-[14px]">qr_code_2</span>
                            Terdaftar
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 text-xs rounded-full">
                            Belum terdaftar
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3.5">
                    @if($user->status === 'active')
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>
                    @elseif($user->status === 'suspended')
                        <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Suspended</span>
                    @else
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Pending</span>
                    @endif
                </td>
                <td class="px-4 py-3.5 text-right">
                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-slate-400/70 transition-colors hover:text-slate-700 dark:text-slate-500/70 dark:hover:text-slate-200" title="Lihat">
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </a>
                        @if($canManageRow)
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-500/60 transition-colors hover:text-blue-600" title="Edit">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </a>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Yakin hapus user ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-500/60 transition-colors hover:text-rose-600" title="Hapus">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                    Tidak ada user ditemukan.
                </td>
            </tr>
        @endforelse
    </x-slot:body>
    @if($users->hasPages())
        <x-slot:footer>
            {{ $users->links() }}
        </x-slot:footer>
    @endif
</x-admin.table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.querySelector('.js-select-all');
        const checkboxes = () => Array.from(document.querySelectorAll('.js-user-checkbox:not(:disabled)'));
        const form = document.getElementById('bulk-action-form');
        const actionSelect = document.querySelector('.js-bulk-action');
        const roleFields = document.querySelector('.js-bulk-role-fields');
        const typeField = document.querySelector('.js-bulk-type-field');
        const bulkWrap = document.querySelector('.js-bulk-menu-wrap');
        const bulkButton = document.querySelector('.js-bulk-menu-button');
        const bulkMenu = document.querySelector('.js-bulk-menu');
        const selectedCounts = Array.from(document.querySelectorAll('.js-selected-count'));
        const selectedCountText = document.querySelector('.js-selected-count-text');

        const updateFields = () => {
            if (!actionSelect || !roleFields || !typeField) return;
            const isType = actionSelect.value === 'type_change';
            const isRole = actionSelect.value === 'roles_add' || actionSelect.value === 'roles_replace';
            roleFields.classList.toggle('hidden', !isRole);
            typeField.classList.toggle('hidden', !isType);
        };

        const updateBulkState = () => {
            const boxes = checkboxes();
            const selected = boxes.filter((cb) => cb.checked).length;

            if (bulkWrap) {
                bulkWrap.classList.toggle('hidden', selected === 0);
            }
            if (bulkMenu && selected === 0) {
                bulkMenu.classList.add('hidden');
            }

            selectedCounts.forEach((item) => {
                item.textContent = selected;
            });
            if (selectedCountText) {
                selectedCountText.textContent = `${selected} user`;
            }

            if (selectAll) {
                const allChecked = boxes.length > 0 && boxes.every((cb) => cb.checked);
                selectAll.checked = allChecked;
                selectAll.indeterminate = selected > 0 && !allChecked;
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                checkboxes().forEach((cb) => {
                    cb.checked = e.target.checked;
                });
                updateBulkState();
            });
        }

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('js-user-checkbox') && selectAll) {
                updateBulkState();
            }
        });

        if (bulkButton && bulkMenu) {
            bulkButton.addEventListener('click', () => {
                bulkMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (event) => {
                if (bulkWrap && !bulkWrap.contains(event.target)) {
                    bulkMenu.classList.add('hidden');
                }
            });
        }

        if (form) {
            form.addEventListener('submit', (e) => {
                const selected = checkboxes().filter((cb) => cb.checked).length;
                if (selected === 0) {
                    e.preventDefault();
                    alert('Pilih minimal satu user.');
                    return;
                }

                if (actionSelect.value === 'delete_selected') {
                    if (!confirm(`Apakah Anda yakin ingin MENGHAPUS ${selected} user yang dipilih? Tindakan ini tidak dapat dibatalkan.`)) {
                        e.preventDefault();
                    }
                } else if (actionSelect.value === 'deactivate_selected') {
                    if (!confirm(`Apakah Anda yakin ingin MENONAKTIFKAN ${selected} user yang dipilih?`)) {
                        e.preventDefault();
                    }
                }
            });
        }

        if (actionSelect) {
            actionSelect.addEventListener('change', updateFields);
            updateFields();
        }

        updateBulkState();
    });
</script>
@endsection
