@extends('layouts.admin')

@section('page-title', 'Detail User')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-3xl"></i>
                        <span class="material-symbols-outlined text-4xl">person</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                        <p class="text-blue-200">{{ '@' . $user->username }}</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">edit</span> Edit
                    </a>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div><span class="text-gray-500 text-sm">Email</span><p class="font-medium">{{ $user->email ?? '-' }}</p></div>
                <div><span class="text-gray-500 text-sm">Tipe</span><p class="font-medium capitalize">{{ $user->type }}</p></div>
                <div><span class="text-gray-500 text-sm">NIS</span><p class="font-medium">{{ $user->nis ?? '-' }}</p></div>
                <div><span class="text-gray-500 text-sm">NIP</span><p class="font-medium">{{ $user->nip ?? '-' }}</p></div>
                <div>
                    <span class="text-gray-500 text-sm">Status</span>
                    <p>
                        @if($user->status === 'active')<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>
                        @elseif($user->status === 'suspended')<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Suspended</span>
                        @else<span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Pending</span>@endif
                    </p>
                </div>
                <div>
                    <span class="text-gray-500 text-sm">Roles</span>
                    <p>@foreach($user->roles as $role)<span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full mr-1">{{ $role->name }}</span>@endforeach</p>
                </div>
                <div><span class="text-gray-500 text-sm">Last Login</span><p class="font-medium">{{ $user->last_login_at?->diffForHumans() ?? '-' }}</p></div>
                <div><span class="text-gray-500 text-sm">Dibuat</span><p class="font-medium">{{ $user->created_at->format('d M Y') }}</p></div>
            </div>
            
            <div class="border-t pt-6">
                <h3 class="font-semibold text-gray-800 mb-3">Log Login Terbaru</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    @forelse($user->loginLogs->take(5) as $log)
                    <div class="flex justify-between py-2 {{ !$loop->last ? 'border-b' : '' }}">
                        <span class="text-gray-600">{{ $log->login_at->format('d M Y H:i') }} - {{ $log->client_app }}</span>
                        <span class="text-gray-400 text-sm">{{ $log->ip_address }}</span>
                    </div>
                    @empty
                    <p class="text-gray-500">Belum ada log login.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
