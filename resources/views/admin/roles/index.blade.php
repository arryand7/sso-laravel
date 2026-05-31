@extends('layouts.admin')
@section('page-title', 'Roles')
@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Daftar Roles</h2>
        <p class="text-gray-600">Kelola role akses user dan mapping aplikasi</p>
    </div>
    @if(auth()->user()?->hasRole('superadmin'))
        <a href="{{ route('admin.roles.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Tambah Role
        </a>
    @endif
</div>
<x-admin.table>
    <x-slot:head>
        <tr>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">Guard</th>
            <th class="px-4 py-3 text-left">Jumlah User</th>
            <th class="px-4 py-3 text-left">Aplikasi</th>
            <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
    </x-slot:head>
    <x-slot:body>
        @foreach($roles as $role)
            @php
                $isSystemRole = in_array($role->name, $systemRoles ?? [], true);
                $applicationCount = (int) ($applicationRoleCounts[$role->id] ?? 0);
            @endphp
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">{{ $role->name }}</span>
                        @if($isSystemRole)
                            <span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded-full">system</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $role->guard_name }}</td>
                <td class="px-4 py-3"><span class="font-medium text-slate-900 dark:text-slate-100">{{ $role->users_count }}</span> users</td>
                <td class="px-4 py-3"><span class="font-medium text-slate-900 dark:text-slate-100">{{ $applicationCount }}</span> aplikasi</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.roles.show', $role) }}" class="text-slate-400/70 transition-colors hover:text-slate-700" title="Lihat Users">
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </a>
                        @if(auth()->user()?->hasRole('superadmin') && ! $isSystemRole)
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-blue-500/60 transition-colors hover:text-blue-600" title="Edit">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </a>
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" onsubmit="return confirm('Yakin hapus role ini?')">
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
        @endforeach
    </x-slot:body>
</x-admin.table>
@endsection
