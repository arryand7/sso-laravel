@extends('layouts.admin')
@section('page-title', 'Tambah Aplikasi')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <form method="POST" action="{{ route('admin.applications.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Nama Aplikasi *</label><input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">@error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Slug *</label><input type="text" name="slug" value="{{ old('slug') }}" required placeholder="e.g., sss, lms" class="w-full px-3 py-2 border border-gray-300 rounded-lg">@error('slug')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Base URL *</label><input type="url" name="base_url" value="{{ old('base_url') }}" required placeholder="https://..." class="w-full px-3 py-2 border border-gray-300 rounded-lg">@error('base_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Redirect URI *</label><input type="text" name="redirect_uri" value="{{ old('redirect_uri') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">@error('redirect_uri')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">SSO Login URL</label><input type="url" name="sso_login_url" value="{{ old('sso_login_url') }}" placeholder="https://app.example.com/sso/login" class="w-full px-3 py-2 border border-gray-300 rounded-lg">@error('sso_login_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label><input type="text" name="category" value="{{ old('category') }}" placeholder="Akademik, LMS, Sarana..." class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Icon (Font Awesome)</label><input type="text" name="icon" value="{{ old('icon') }}" placeholder="fa-graduation-cap" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label><textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('description') }}</textarea></div>
                <div class="md:col-span-2"><label class="flex items-center"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-blue-600"><span class="ml-2 text-gray-700">Aktif</span></label></div>
            </div>
            <div class="mt-6"><label class="block text-sm font-medium text-gray-700 mb-2">Roles dengan Akses *</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">@foreach($roles as $role)<label class="flex items-center space-x-2"><input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600"><span class="text-gray-700">{{ $role->name }}</span></label>@endforeach</div>
                @error('roles')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mt-8 flex justify-end space-x-3"><a href="{{ route('admin.applications.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg">Batal</a><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan Aplikasi</button></div>
        </form>
    </div>
</div>
@endsection
