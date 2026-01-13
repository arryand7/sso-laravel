@extends('layouts.admin')
@section('page-title', 'Role: ' . $role->name)
@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">Users dengan Role: <span class="text-blue-600">{{ $role->name }}</span></h2></div>
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
