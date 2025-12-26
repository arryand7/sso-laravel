@extends('layouts.admin')
@section('page-title', 'Reset Password')
@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Reset Password: {{ $user->name }}</h2>
        <form method="POST" action="{{ route('admin.users.reset-password.update', $user) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru *</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2 border border-gray-300 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Reset Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
