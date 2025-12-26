@extends('layouts.admin')
@section('page-title', 'Role: ' . $role->name)
@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">Users dengan Role: <span class="text-blue-600">{{ $role->name }}</span></h2></div>
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-medium">{{ $user->name }}</td>
                <td class="px-6 py-4">{{ $user->username }}</td>
                <td class="px-6 py-4">{{ $user->email ?? '-' }}</td>
                <td class="px-6 py-4">@if($user->status === 'active')<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>@else<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">{{ $user->status }}</span>@endif</td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Tidak ada user dengan role ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())<div class="px-6 py-4 border-t">{{ $users->links() }}</div>@endif
</div>
@endsection
