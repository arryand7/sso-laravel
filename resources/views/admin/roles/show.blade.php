@extends('layouts.admin')
@section('page-title', 'Role: ' . $role->name)
@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Users dengan Role: <span class="text-blue-600">{{ $role->name }}</span></h2>
        <p class="text-gray-600">Daftar user yang saat ini memiliki role ini</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Kembali</a>
        @if(auth()->user()?->hasRole('superadmin') && ! $isSystemRole)
            <a href="{{ route('admin.roles.edit', $role) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">edit</span>
                Edit Role
            </a>
        @endif
    </div>
</div>
<x-admin.table>
    <x-slot:head>
        <tr>
            <th class="px-4 py-3 text-left">Nama</th>
            <th class="px-4 py-3 text-left">Username</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Status</th>
        </tr>
    </x-slot:head>
    <x-slot:body>
        @forelse($users as $user)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</td>
                <td class="px-4 py-3">{{ $user->username }}</td>
                <td class="px-4 py-3">{{ $user->email ?? '-' }}</td>
                <td class="px-4 py-3">
                    @if($user->status === 'active')
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>
                    @else
                        <span class="px-2 py-1 bg-slate-100 text-slate-700 text-xs rounded-full">{{ $user->status }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">Tidak ada user dengan role ini.</td></tr>
        @endforelse
    </x-slot:body>
    @if($users->hasPages())
        <x-slot:footer>{{ $users->links() }}</x-slot:footer>
    @endif
</x-admin.table>
@endsection
