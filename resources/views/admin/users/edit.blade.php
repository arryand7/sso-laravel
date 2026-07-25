@extends('layouts.admin')

@section('page-title', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <!-- Photo Upload & Display Section -->
            <div class="mb-6 pb-6 border-b">
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto Profil</label>
                <div class="flex flex-col sm:flex-row items-start gap-4">
                    <div class="w-32 h-24 bg-slate-100 border rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0 relative">
                        <img id="photo-preview" src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-contain">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="photo" id="photo-input" accept="image/jpeg,image/png,image/webp"
                               class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                            Foto akan disesuaikan ke bingkai 4:3 tanpa dipotong atau ditarik. Jika rasio foto berbeda, area kosong akan ditambahkan secara otomatis. Ukuran file hasil akhir akan dioptimalkan hingga sekitar 300 KB.
                        </p>
                        @error('photo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror

                        @if($user->photo_path)
                            <div class="mt-2">
                                <button type="button" onclick="document.getElementById('delete-photo-form').submit();" class="text-xs text-rose-600 hover:text-rose-800 font-medium inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">delete</span> Hapus Foto Profil
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

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

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kode QR Kartu Anggota</label>
                    <input type="text" name="qr_code" value="{{ old('qr_code', $user->qr_code) }}"
                           placeholder="Contoh: 00001234"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Kode QR dari kartu anggota fisik. Harus unik jika diisi.</p>
                    @error('qr_code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
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
            
            <div class="mt-8 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
                    <div>
                        <h4 class="font-medium text-gray-900">Reset Password</h4>
                        <p class="text-sm text-gray-500">Set password baru untuk user ini.</p>
                    </div>
                    <a href="{{ route('admin.users.reset-password', $user) }}" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2 ml-4">
                        <span class="material-symbols-outlined text-[20px]">key</span> Reset Password
                    </a>
                </div>
                <div class="space-x-3 text-right">
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update User
                    </button>
                </div>
            </div>
        </form>

        @if($user->photo_path)
            <form id="delete-photo-form" method="POST" action="{{ route('admin.users.photo.destroy', $user) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
</div>

<script>
    document.getElementById('photo-input')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('photo-preview');
        if (file && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection
