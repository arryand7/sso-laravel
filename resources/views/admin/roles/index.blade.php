@extends('layouts.admin')
@section('page-title', 'Roles')
@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">Daftar Roles</h2><p class="text-gray-600">Lihat roles dan jumlah user</p></div>
<x-admin.table>
    <x-slot:head>
        <tr>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">Guard</th>
            <th class="px-4 py-3 text-left">Jumlah User</th>
            <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
    </x-slot:head>
    <x-slot:body>
        @foreach($roles as $role)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3"><span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">{{ $role->name }}</span></td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $role->guard_name }}</td>
                <td class="px-4 py-3"><span class="font-medium text-slate-900 dark:text-slate-100">{{ $role->users_count }}</span> users</td>
                <td class="px-4 py-3 text-right"><a href="{{ route('admin.roles.show', $role) }}" class="text-blue-600 hover:text-blue-800">Lihat Users</a></td>
            </tr>
        @endforeach
    </x-slot:body>
</x-admin.table>
@endsection
