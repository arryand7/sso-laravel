@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-8 text-white">
            <div class="flex items-center space-x-4">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-4xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                    <p class="text-blue-200">{{ '@' . $user->username }}</p>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Profil</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm text-gray-500">Nama Lengkap</label>
                    <p class="font-medium text-gray-900">{{ $user->name }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Username</label>
                    <p class="font-medium text-gray-900">{{ $user->username }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Email</label>
                    <p class="font-medium text-gray-900">{{ $user->email ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Tipe User</label>
                    <p class="font-medium text-gray-900 capitalize">{{ $user->type }}</p>
                </div>
                @if($user->nis)
                <div>
                    <label class="text-sm text-gray-500">NIS</label>
                    <p class="font-medium text-gray-900">{{ $user->nis }}</p>
                </div>
                @endif
                @if($user->nip)
                <div>
                    <label class="text-sm text-gray-500">NIP</label>
                    <p class="font-medium text-gray-900">{{ $user->nip }}</p>
                </div>
                @endif
                <div>
                    <label class="text-sm text-gray-500">Role</label>
                    <p class="font-medium text-gray-900">
                        @foreach($user->roles as $role)
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full mr-1">{{ $role->name }}</span>
                        @endforeach
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Status</label>
                    <p class="font-medium">
                        @if($user->status === 'active')
                            <span class="text-green-600">Aktif</span>
                        @elseif($user->status === 'suspended')
                            <span class="text-red-600">Suspended</span>
                        @else
                            <span class="text-yellow-600">Pending</span>
                        @endif
                    </p>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t flex flex-wrap gap-3">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-2"></i> Edit Profil
                </a>
                <a href="{{ route('profile.password') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-key mr-2"></i> Ganti Password
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
