@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6">
        <h1 class="text-xl font-bold text-gray-900 mb-6">Edit Profil</h1>
        
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Photo Upload Section -->
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
                                    <i class="fas fa-trash-alt text-[12px]"></i> Hapus Foto Profil
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $user->name) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="{{ old('email', $user->email) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" 
                       value="{{ $user->username }}"
                       class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500"
                       disabled>
                <p class="text-gray-500 text-sm mt-1">Username tidak dapat diubah.</p>
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="{{ route('profile.show') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>

        @if($user->photo_path)
            <form id="delete-photo-form" method="POST" action="{{ route('profile.photo.destroy') }}" class="hidden">
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
