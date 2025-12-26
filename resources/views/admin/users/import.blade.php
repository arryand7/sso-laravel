@extends('layouts.admin')
@section('page-title', 'Import User')
@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Import User dari File</h2>
        <form method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">File Excel/CSV</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <p class="text-gray-500 text-sm mt-1">Format: XLSX, XLS, atau CSV. Max 10MB.</p>
                @error('file')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-blue-800 mb-2">Format Kolom:</h4>
                <ul class="text-sm text-blue-700 list-disc list-inside">
                    <li>name (wajib)</li><li>username (wajib)</li><li>email</li><li>type (student/teacher/parent/staff)</li>
                    <li>nis</li><li>nip</li><li>role</li>
                </ul>
            </div>
            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
