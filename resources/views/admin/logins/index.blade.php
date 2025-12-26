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

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aplikasi</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4"><div class="font-medium">{{ $log->user->name ?? 'Unknown' }}</div><div class="text-sm text-gray-500">{{ $log->user->username ?? '' }}</div></td>
                <td class="px-6 py-4"><span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">{{ $log->client_app }}</span></td>
                <td class="px-6 py-4 text-gray-600">{{ $log->ip_address }}</td>
                <td class="px-6 py-4 text-gray-600"><span title="{{ $log->login_at }}">{{ $log->login_at->format('d M Y H:i') }}</span></td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Tidak ada log login.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($logs->hasPages())<div class="px-6 py-4 border-t">{{ $logs->links() }}</div>@endif
</div>
@endsection
