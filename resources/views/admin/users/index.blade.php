@extends('layouts.admin')

@section('page-title', 'Manajemen User')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Daftar User</h2>
        <p class="text-gray-600">Kelola semua user di sistem Sabira Connect</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('admin.users.import') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">upload_file</span> Import
        </a>
        <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">add</span> Tambah User
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm border mb-6 p-4">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm text-gray-600 mb-1">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Nama, username, email, NIS, NIP..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Tipe</label>
            <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                <option value="">Semua</option>
                <option value="student" {{ request('type') === 'student' ? 'selected' : '' }}>Student</option>
                <option value="teacher" {{ request('type') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                <option value="parent" {{ request('type') === 'parent' ? 'selected' : '' }}>Parent</option>
                <option value="staff" {{ request('type') === 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="admin" {{ request('type') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                <option value="">Semua</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Role</label>
            <select name="role" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                <option value="">Semua</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Tampilkan</label>
            <select name="per_page" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                @foreach([10, 15, 25, 50, 100] as $size)
                    <option value="{{ $size }}" {{ (int)request('per_page', $perPage ?? 15) === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">search</span> Filter
        </button>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">Reset</a>
    </form>
</div>

<form method="POST" action="{{ route('admin.users.bulk-actions') }}" id="bulk-action-form">
    @csrf
    <div class="bg-white rounded-lg shadow-sm border mb-6 p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Aksi</label>
                <select name="action" required class="js-bulk-action px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                    <option value="roles_add">Tambah role</option>
                    <option value="roles_replace">Ganti role</option>
                    <option value="type_change">Ubah tipe user</option>
                </select>
            </div>
            <div class="js-bulk-role-fields min-w-[220px] flex-1">
                <label class="block text-sm text-gray-600 mb-1">Roles</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($roles as $role)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded border-gray-300 text-blue-600">
                            {{ $role->name }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="js-bulk-type-field hidden">
                <label class="block text-sm text-gray-600 mb-1">Tipe User</label>
                <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none">
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="parent">Parent</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">done_all</span> Terapkan
            </button>
            <p class="text-xs text-gray-500">Centang user pada tabel untuk menerapkan.</p>
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
<x-admin.table
    :ordering="false"
    :dtPaging="false"
    :dtInfo="false"
    :dtSearch="true"
    :dtLengthChange="false"
    dtDom="<'row mb-3 align-items-center'<'col-sm-12 col-md-7 d-flex align-items-center gap-2'B><'col-sm-12 col-md-5 d-flex align-items-center justify-end'f>>t">
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
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="js-user-checkbox rounded border-gray-300 text-blue-600">
                </td>
                <td class="px-4 py-3">
                    <div>
                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email ?? '-' }}</div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span class="text-slate-900 dark:text-slate-100">{{ $user->username }}</span>
                    @if($user->nis)
                        <div class="text-xs text-slate-500 dark:text-slate-400">NIS: {{ $user->nis }}</div>
                    @elseif($user->nip)
                        <div class="text-xs text-slate-500 dark:text-slate-400">NIP: {{ $user->nip }}</div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="capitalize text-slate-700 dark:text-slate-300">{{ $user->type }}</span>
                </td>
                <td class="px-4 py-3">
                    @foreach($user->roles as $role)
                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">{{ $role->name }}</span>
                    @endforeach
                </td>
                <td class="px-4 py-3">
                    @if($user->status === 'active')
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>
                    @elseif($user->status === 'suspended')
                        <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Suspended</span>
                    @else
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Pending</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300" title="Lihat">
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-500 hover:text-blue-600" title="Edit">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </a>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Yakin hapus user ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-500 hover:text-rose-600" title="Hapus">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </form>
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
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.querySelector('.js-select-all');
        const checkboxes = () => Array.from(document.querySelectorAll('.js-user-checkbox'));
        const form = document.getElementById('bulk-action-form');
        const actionSelect = document.querySelector('.js-bulk-action');
        const roleFields = document.querySelector('.js-bulk-role-fields');
        const typeField = document.querySelector('.js-bulk-type-field');
        const searchInput = document.querySelector('.dataTables_filter input');

        const updateFields = () => {
            if (!actionSelect || !roleFields || !typeField) return;
            const isType = actionSelect.value === 'type_change';
            roleFields.classList.toggle('hidden', isType);
            typeField.classList.toggle('hidden', !isType);
        };

        const bindServerSearch = () => {
            if (!searchInput) return;

            const params = new URLSearchParams(window.location.search);
            const initial = params.get('search') || '';
            searchInput.value = initial;

            const wrapper = searchInput.closest('.dataTables_filter');
            if (wrapper) {
                searchInput.setAttribute('placeholder', 'kata kunci pencarian');
            }

            const $input = window.jQuery ? window.jQuery(searchInput) : null;
            if ($input) {
                $input.off('.DT');
            }

            let timer = null;
            searchInput.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const value = searchInput.value.trim();
                    if (value) {
                        params.set('search', value);
                    } else {
                        params.delete('search');
                    }
                    params.set('page', '1');
                    window.location.search = params.toString();
                }, 450);
            });
        };

        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                checkboxes().forEach((cb) => {
                    cb.checked = e.target.checked;
                });
            });
        }

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('js-user-checkbox') && selectAll) {
                const boxes = checkboxes();
                const allChecked = boxes.length > 0 && boxes.every((cb) => cb.checked);
                selectAll.checked = allChecked;
            }
        });

        if (form) {
            form.addEventListener('submit', (e) => {
                const anyChecked = checkboxes().some((cb) => cb.checked);
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Pilih minimal satu user.');
                }
            });
        }

        if (actionSelect) {
            actionSelect.addEventListener('change', updateFields);
            updateFields();
        }

        bindServerSearch();
    });
</script>
@endsection
