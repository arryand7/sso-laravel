@extends('layouts.admin')

@section('page-title', 'Daftar Aplikasi')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Daftar Aplikasi</h2>
        <p class="text-gray-600">Kelola aplikasi klien OAuth2</p>
    </div>
    <a href="{{ route('admin.applications.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
        <span class="material-symbols-outlined text-[20px]">add</span> Tambah Aplikasi
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm border mb-6 p-4">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]"><label class="block text-sm text-gray-600 mb-1">Cari</label><input type="text" name="search" value="{{ request('search') }}" placeholder="Nama aplikasi..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none"></div>
        <div><label class="block text-sm text-gray-600 mb-1">Kategori</label><input type="text" name="category" value="{{ request('category') }}" placeholder="Akademik..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/50 outline-none"></div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 flex items-center gap-2"><span class="material-symbols-outlined text-[20px]">search</span> Filter</button>
    </form>
</div>

<x-admin.table>
    <x-slot:head>
        <tr>
            <th class="px-4 py-3 text-left">Aplikasi</th>
            <th class="px-4 py-3 text-left">URL</th>
            <th class="px-4 py-3 text-left">Kategori</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
    </x-slot:head>
    <x-slot:body>
        @forelse($applications as $app)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <div class="flex items-center">
                        <div class="size-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mr-3 overflow-hidden">
                            @if($app->logo_url)
                                <img src="{{ $app->logo_url }}" alt="Logo {{ $app->name }}" class="h-full w-full object-cover">
                            @else
                                <span class="material-symbols-outlined">{{ $app->icon ?? 'apps' }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="font-medium text-slate-900 dark:text-slate-100">{{ $app->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $app->slug }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="text-sm text-slate-600 dark:text-slate-300 truncate max-w-xs" title="{{ $app->base_url }}">{{ $app->base_url }}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded-md">{{ $app->category }}</span>
                </td>
                <td class="px-4 py-3">
                    @if($app->is_active)
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>
                    @else
                        <span class="px-2 py-1 bg-slate-100 text-slate-700 text-xs rounded-full">Inactive</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('admin.applications.show', $app) }}" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300" title="Detail"><span class="material-symbols-outlined text-[20px]">visibility</span></a>
                        <a href="{{ route('admin.applications.edit', $app) }}" class="text-blue-500 hover:text-blue-600" title="Edit"><span class="material-symbols-outlined text-[20px]">edit</span></a>
                        <form method="POST" action="{{ route('admin.applications.destroy', $app) }}" class="inline" onsubmit="return confirm('Hapus aplikasi?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-rose-500 hover:text-rose-600" title="Hapus"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">Tidak ada aplikasi.</td></tr>
        @endforelse
    </x-slot:body>
    @if($applications->hasPages())
        <x-slot:footer>{{ $applications->links() }}</x-slot:footer>
    @endif
</x-admin.table>
@endsection
