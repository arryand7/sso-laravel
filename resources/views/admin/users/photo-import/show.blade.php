@extends('layouts.admin')

@section('page-title', 'Preview Import Foto ZIP')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if (session('status'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">error</span>
            {{ session('error') }}
        </div>
    @endif

    <!-- Batch Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">Batch Import Foto #{{ $batch->id }}</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $batch->status_label }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    File: <span class="font-semibold text-slate-700">{{ $batch->original_filename }}</span> | 
                    Pencocokan: <span class="font-semibold text-slate-700">{{ $batch->matching_type_label }}</span> | 
                    Kebijakan Foto Existing: <span class="font-semibold text-slate-700">{{ $batch->policy_label }}</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.users.photo-import.report', $batch) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold transition-colors">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Download Laporan Excel
                </a>

                @if ($batch->isCancellable())
                    <form method="POST" action="{{ route('admin.users.photo-import.cancel', $batch) }}" onsubmit="return confirm('Yakin ingin membatalkan batch ini?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition-colors">
                            <span class="material-symbols-outlined text-[18px]">cancel</span>
                            Batalkan Batch
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Live Progress Bar if Importing -->
        <div id="progress-container" class="{{ $batch->status === 'importing' ? '' : 'hidden' }} mt-6 p-4 bg-blue-50/60 border border-blue-200 rounded-xl space-y-2">
            <div class="flex items-center justify-between text-xs font-semibold text-blue-900">
                <span id="progress-status-text">Memproses foto...</span>
                <span id="progress-count-text">0 / {{ $batch->ready_new_count + $batch->ready_replace_count }}</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-3 overflow-hidden">
                <div id="progress-bar" class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>

        <!-- Summary Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-center">
                <div class="text-xs font-medium text-slate-500">Total File ZIP</div>
                <div class="text-lg font-bold text-slate-900 mt-1">{{ number_format($batch->total_entries) }}</div>
            </div>
            <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl text-center">
                <div class="text-xs font-medium text-emerald-700">Foto Baru (Import)</div>
                <div class="text-lg font-bold text-emerald-900 mt-1">{{ number_format($batch->ready_new_count) }}</div>
            </div>
            <div class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl text-center">
                <div class="text-xs font-medium text-blue-700">Foto Existing (Replace)</div>
                <div class="text-lg font-bold text-blue-900 mt-1">{{ number_format($batch->ready_replace_count) }}</div>
            </div>
            <div class="p-3.5 bg-slate-100 border border-slate-200 rounded-xl text-center">
                <div class="text-xs font-medium text-slate-600">Dilewati (Skip)</div>
                <div class="text-lg font-bold text-slate-800 mt-1">{{ number_format($batch->skipped_count) }}</div>
            </div>
            <div class="p-3.5 bg-rose-50/70 border border-rose-200 rounded-xl text-center">
                <div class="text-xs font-medium text-rose-700">Konflik / Error</div>
                <div class="text-lg font-bold text-rose-900 mt-1">{{ number_format($batch->failed_count) }}</div>
            </div>
            <div class="p-3.5 bg-purple-50/70 border border-purple-200 rounded-xl text-center">
                <div class="text-xs font-medium text-purple-700">Selesai Diproses</div>
                <div class="text-lg font-bold text-purple-900 mt-1">{{ number_format($batch->processed_count) }}</div>
            </div>
        </div>
    </div>

    <!-- Items Table & Filters Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Filter Status Tabs -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                <a href="{{ route('admin.users.photo-import.show', [$batch, 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg border {{ !$currentStatus ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100' }}">
                    Semua Status
                </a>
                <a href="{{ route('admin.users.photo-import.show', [$batch, 'status' => 'MATCHED_NEW', 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg border {{ $currentStatus === 'MATCHED_NEW' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-emerald-700 border-slate-300 hover:bg-slate-100' }}">
                    Siap Import
                </a>
                <a href="{{ route('admin.users.photo-import.show', [$batch, 'status' => 'MATCHED_REPLACE', 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg border {{ $currentStatus === 'MATCHED_REPLACE' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-700 border-slate-300 hover:bg-slate-100' }}">
                    Siap Ganti
                </a>
                <a href="{{ route('admin.users.photo-import.show', [$batch, 'status' => 'SKIPPED_EXISTING', 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg border {{ $currentStatus === 'SKIPPED_EXISTING' ? 'bg-slate-700 text-white border-slate-700' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100' }}">
                    Dilewati
                </a>
                <a href="{{ route('admin.users.photo-import.show', [$batch, 'status' => 'USER_NOT_FOUND', 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg border {{ $currentStatus === 'USER_NOT_FOUND' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-amber-700 border-slate-300 hover:bg-slate-100' }}">
                    Tidak Ditemukan
                </a>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.users.photo-import.show', $batch) }}" class="flex items-center gap-2">
                @if ($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama file / identifier..." 
                       class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-semibold rounded-lg hover:bg-slate-700">
                    Cari
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                        <th class="p-3">Nama File Asli</th>
                        <th class="p-3">Identifier</th>
                        <th class="p-3">User Cocok</th>
                        <th class="p-3">Foto Lama</th>
                        <th class="p-3">Status Preview</th>
                        <th class="p-3">Rencana Aksi</th>
                        <th class="p-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3 font-semibold text-slate-800 font-mono">{{ $item->original_filename }}</td>
                            <td class="p-3 font-mono font-bold text-blue-700">
                                {{ strtoupper($item->identifier_type) }}: {{ $item->identifier }}
                            </td>
                            <td class="p-3">
                                @if ($item->user)
                                    <div class="font-semibold text-slate-900">{{ $item->user->name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ ucfirst($item->user->type) }}</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($item->old_photo_path)
                                    <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                                        <span class="material-symbols-outlined text-[16px]">photo_camera</span> Ada
                                    </span>
                                @else
                                    <span class="text-slate-400">Tidak ada</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $item->status_badge_class }}">
                                    {{ $item->status_label }}
                                </span>
                            </td>
                            <td class="p-3">
                                @if ($item->planned_action === 'import')
                                    <span class="text-emerald-700 font-bold">Import Foto</span>
                                @elseif ($item->planned_action === 'replace')
                                    <span class="text-blue-700 font-bold">Ganti Foto</span>
                                @else
                                    <span class="text-slate-500">Tidak Ada Perubahan</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-600 max-w-xs truncate" title="{{ $item->error_message }}">
                                {{ $item->error_message ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                Tidak ada item preview yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    <!-- Confirm Import Action Bar -->
    @if ($batch->isCommittable())
        <div class="p-6 bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Konfirmasi dan Jalankan Import Foto</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    <strong>{{ number_format($batch->ready_new_count) }}</strong> foto baru akan dipasang, 
                    <strong>{{ number_format($batch->ready_replace_count) }}</strong> foto existing akan diganti, dan 
                    <strong>{{ number_format($batch->skipped_count + $batch->failed_count) }}</strong> file tidak akan diubah.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.users.photo-import.confirm', $batch) }}" onsubmit="return confirm('Mulai proses import foto sekarang?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-sm transition-colors flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    Konfirmasi & Jalankan Import
                </button>
            </form>
        </div>
    @endif

</div>

@if ($batch->status === 'importing')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const progressUrl = "{{ route('admin.users.photo-import.progress', $batch) }}";
    
    function checkProgress() {
        fetch(progressUrl)
            .then(res => res.json())
            .then(data => {
                const bar = document.getElementById('progress-bar');
                const statusText = document.getElementById('progress-status-text');
                const countText = document.getElementById('progress-count-text');

                if (bar && statusText && countText) {
                    bar.style.width = data.progress_percent + '%';
                    statusText.textContent = data.status_label;
                    countText.textContent = data.processed_count + ' / ' + data.total_to_import + ' (' + data.progress_percent + '%)';
                }

                if (data.is_completed) {
                    window.location.reload();
                } else {
                    setTimeout(checkProgress, 3000);
                }
            })
            .catch(err => {
                console.error('Error polling progress:', err);
                setTimeout(checkProgress, 5000);
            });
    }

    checkProgress();
});
</script>
@endif

@endsection
