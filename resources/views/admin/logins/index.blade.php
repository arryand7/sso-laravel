@extends('layouts.admin')
@section('page-title', 'Log Login')
@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">Log Login</h2><p class="text-gray-600">Riwayat login user ke semua aplikasi</p></div>

<div class="bg-white rounded-lg shadow-sm border mb-6 p-4">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]"><label class="block text-sm text-gray-600 mb-1">Cari User</label><input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau username..." class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
        <div><label class="block text-sm text-gray-600 mb-1">Aplikasi</label><select name="client_app" class="px-3 py-2 border border-gray-300 rounded-lg"><option value="">Semua</option>@foreach($clientApps as $app)<option value="{{ $app }}" {{ request('client_app') === $app ? 'selected' : '' }}>{{ $app }}</option>@endforeach</select></div>
        <div><label class="block text-sm text-gray-600 mb-1">Dari Tanggal</label><input type="date" name="start_date" value="{{ request('start_date') }}" class="px-3 py-2 border border-gray-300 rounded-lg"></div>
        <div><label class="block text-sm text-gray-600 mb-1">Sampai Tanggal</label><input type="date" name="end_date" value="{{ request('end_date') }}" class="px-3 py-2 border border-gray-300 rounded-lg"></div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg">Filter</button>
    </form>
</div>

<x-admin.table>
    <x-slot:head>
        <tr>
            <th class="px-4 py-3 text-left">User</th>
            <th class="px-4 py-3 text-left">Aplikasi</th>
            <th class="px-4 py-3 text-left">IP Address</th>
            <th class="px-4 py-3 text-left">Waktu</th>
        </tr>
    </x-slot:head>
    <x-slot:body>
        @forelse($logs as $log)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ $log->user->name ?? 'Unknown' }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ $log->user->username ?? '' }}</div>
                </td>
                <td class="px-4 py-3"><span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">{{ $log->client_app }}</span></td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $log->ip_address }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300"><span title="{{ $log->login_at }}">{{ $log->login_at->format('d M Y H:i') }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">Tidak ada log login.</td></tr>
        @endforelse
    </x-slot:body>
    @if($logs->hasPages())
        <x-slot:footer>{{ $logs->links() }}</x-slot:footer>
    @endif
</x-admin.table>
@endsection
