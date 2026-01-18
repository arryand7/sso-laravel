@extends('layouts.admin')
@section('page-title', 'Edit Aplikasi')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <form method="POST" action="{{ route('admin.applications.update', $application) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Nama Aplikasi *</label><input type="text" name="name" value="{{ old('name', $application->name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Slug *</label><input type="text" name="slug" value="{{ old('slug', $application->slug) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Base URL *</label><input type="url" name="base_url" value="{{ old('base_url', $application->base_url) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Redirect URI *</label><input type="text" name="redirect_uri" value="{{ old('redirect_uri', $application->redirect_uri) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">SSO Login URL</label><input type="url" name="sso_login_url" value="{{ old('sso_login_url', $application->sso_login_url) }}" placeholder="https://app.example.com/sso/login" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label><input type="text" name="category" value="{{ old('category', $application->category) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo Aplikasi</label>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">Logo akan otomatis disesuaikan menjadi ukuran 256x256.</p>
                    @if($application->logo_url)
                        <img src="{{ $application->logo_url }}" alt="Logo {{ $application->name }}" class="mt-3 h-12 w-12 rounded-lg border border-slate-200 object-cover">
                    @endif
                    @error('logo')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label><textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('description', $application->description) }}</textarea></div>
                <div class="md:col-span-2"><label class="flex items-center"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $application->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600"><span class="ml-2 text-gray-700">Aktif</span></label></div>
            </div>
            <div class="mt-6"><label class="block text-sm font-medium text-gray-700 mb-2">Roles dengan Akses *</label>
                @php $appRoleIds = $application->roles->pluck('id')->toArray(); @endphp
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">@foreach($roles as $role)<label class="flex items-center space-x-2"><input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, old('roles', $appRoleIds)) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600"><span class="text-gray-700">{{ $role->name }}</span></label>@endforeach</div>
            </div>
            <div class="mt-8 flex justify-end space-x-3"><a href="{{ route('admin.applications.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg">Batal</a><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update Aplikasi</button></div>
        </form>
    </div>
</div>
@endsection
