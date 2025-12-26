@extends('layouts.admin')

@section('page-title', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipe User *</label>
                    <select name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="student" {{ old('type', $user->type) === 'student' ? 'selected' : '' }}>Student</option>
                        <option value="teacher" {{ old('type', $user->type) === 'teacher' ? 'selected' : '' }}>Teacher</option>
                        <option value="parent" {{ old('type', $user->type) === 'parent' ? 'selected' : '' }}>Parent</option>
                        <option value="staff" {{ old('type', $user->type) === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="admin" {{ old('type', $user->type) === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ old('status', $user->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIS</label>
                    <input type="text" name="nis" value="{{ old('nis', $user->nis) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIP</label>
                    <input type="text" name="nip" value="{{ old('nip', $user->nip) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Roles *</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @php $userRoleIds = $user->roles->pluck('id')->toArray(); @endphp
                    @foreach($roles as $role)
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                               {{ in_array($role->id, old('roles', $userRoleIds)) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="text-gray-700">{{ $role->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            
            <div class="mt-8 flex justify-between">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
                    <div>
                        <h4 class="font-medium text-gray-900">Reset Password</h4>
                        <p class="text-sm text-gray-500">Kirim link reset password ke email user atau set manual.</p>
                    </div>
                    <a href="{{ route('admin.users.reset-password', $user) }}" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">key</span> Reset Password
                    </a>
                </div>
                <div class="space-x-3">
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
