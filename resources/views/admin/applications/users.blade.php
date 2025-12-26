@extends('layouts.admin')
@section('page-title', 'Users - ' . $application->name)
@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-gray-600 mb-2">
        <a href="{{ route('admin.applications.index') }}" class="hover:text-blue-600">Aplikasi</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('admin.applications.show', $application) }}" class="hover:text-blue-600">{{ $application->name }}</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span>Users</span>
    </div>
    <h2 class="text-2xl font-bold text-gray-900">Users dengan Akses ke {{ $application->name }}</h2>
</div>

<div class="bg-white rounded-lg shadow-sm border mb-6 p-4">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm text-gray-600 mb-1">Cari</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama, username, email..." class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Tipe</label>
            <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">Semua</option>
                @foreach(['student', 'teacher', 'parent', 'staff', 'admin'] as $type)
                <option value="{{ $type }}" {{ ($filters['type'] ?? '') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">Semua</option>
                @foreach(['active', 'suspended', 'pending'] as $status)
                <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">search</span> Filter
        </button>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roles</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4"><div class="font-medium text-gray-900">{{ $user->name }}</div><div class="text-sm text-gray-500">{{ $user->email ?? '-' }}</div></td>
                <td class="px-6 py-4">{{ $user->username }}</td>
                <td class="px-6 py-4 capitalize">{{ $user->type }}</td>
                <td class="px-6 py-4">@foreach($user->roles as $role)<span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full mr-1">{{ $role->name }}</span>@endforeach</td>
                <td class="px-6 py-4">@if($user->status === 'active')<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>@elseif($user->status === 'suspended')<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Suspended</span>@else<span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Pending</span>@endif</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Tidak ada user ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())<div class="px-6 py-4 border-t">{{ $users->links() }}</div>@endif
</div>
@endsection
