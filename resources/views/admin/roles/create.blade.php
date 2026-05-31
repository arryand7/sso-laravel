@extends('layouts.admin')

@section('page-title', 'Tambah Role')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900">Tambah Role</h2>
            <p class="text-gray-600">Buat role baru untuk pengaturan akses aplikasi.</p>
        </div>

        <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Role *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="contoh: finance_staff"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="mt-2 text-xs text-gray-500">Gunakan huruf, angka, dash, atau underscore. Role akan disimpan lowercase.</p>
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan Role</button>
            </div>
        </form>
    </div>
</div>
@endsection
