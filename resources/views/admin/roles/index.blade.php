@extends('layouts.admin')
@section('page-title', 'Roles')
@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">Daftar Roles</h2><p class="text-gray-600">Lihat roles dan jumlah user</p></div>
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guard</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah User</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($roles as $role)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4"><span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">{{ $role->name }}</span></td>
                <td class="px-6 py-4 text-gray-600">{{ $role->guard_name }}</td>
                <td class="px-6 py-4"><span class="font-medium">{{ $role->users_count }}</span> users</td>
                <td class="px-6 py-4 text-right"><a href="{{ route('admin.roles.show', $role) }}" class="text-blue-600 hover:text-blue-800">Lihat Users</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
