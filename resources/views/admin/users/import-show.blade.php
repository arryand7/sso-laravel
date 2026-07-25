@extends('layouts.admin')

@section('page-title', 'Detail Batch Import')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header & Summary Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-xl font-bold text-slate-900">{{ $batch->original_filename }}</h2>
                    @php
                        $statusClasses = match($batch->status) {
                            'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-300',
                            'ready' => 'bg-blue-100 text-blue-700 border-blue-300',
                            'validation_failed', 'failed' => 'bg-rose-100 text-rose-700 border-rose-300',
                            'validating', 'importing' => 'bg-amber-100 text-amber-700 border-amber-300 animate-pulse',
                            'cancelled' => 'bg-slate-100 text-slate-500 border-slate-300',
                            default => 'bg-slate-100 text-slate-600 border-slate-300'
                        };
                    @endphp
                    <span class="px-3 py-1 text-xs font-bold rounded-full border {{ $statusClasses }}">
                        {{ $batch->status_label }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-mono">UUID: {{ $batch->uuid }} | Uploaded by {{ $batch->uploader?->name ?? 'System' }} | {{ $batch->created_at->format('d M Y H:i:s') }}</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.users.import') }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-medium">
                    Kembali
                </a>

                @if($batch->invalid_rows > 0 || $batch->status === 'validation_failed')
                    <a href="{{ route('admin.users.import.report', $batch) }}" 
                       class="px-4 py-2 bg-rose-50 text-rose-700 border border-rose-200 rounded-lg hover:bg-rose-100 text-sm font-semibold inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        Download Laporan Error XLSX
                    </a>
                @endif

                @if($batch->isCommittable())
                    <form method="POST" action="{{ route('admin.users.import.commit', $batch) }}" onsubmit="return confirm('Konfirmasi commit import {{ $batch->valid_rows }} user ke database?')">
                        @csrf
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-2 shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">done_all</span>
                            Konfirmasi & Commit Import
                        </button>
                    </form>
                @endif

                @if($batch->isCancellable())
                    <form method="POST" action="{{ route('admin.users.import.cancel', $batch) }}" onsubmit="return confirm('Batalkan batch import ini?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium">
                            Batalkan Batch
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Summary Statistics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 pt-6">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                <span class="text-xs text-slate-500 font-medium">Mode Import</span>
                <p class="text-sm font-bold text-slate-800 mt-1">{{ $batch->mode_label }}</p>
            </div>
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                <span class="text-xs text-slate-500 font-medium">Total Baris</span>
                <p class="text-lg font-bold text-slate-900 mt-1">{{ $batch->total_rows }}</p>
            </div>
            <div class="bg-emerald-50/60 p-4 rounded-xl border border-emerald-100">
                <span class="text-xs text-emerald-700 font-medium">Baris Valid</span>
                <p class="text-lg font-bold text-emerald-800 mt-1">{{ $batch->valid_rows }}</p>
            </div>
            <div class="bg-rose-50/60 p-4 rounded-xl border border-rose-100">
                <span class="text-xs text-rose-700 font-medium">Baris Invalid / Error</span>
                <p class="text-lg font-bold text-rose-800 mt-1">{{ $batch->invalid_rows }}</p>
            </div>
            <div class="bg-blue-50/60 p-4 rounded-xl border border-blue-100">
                <span class="text-xs text-blue-700 font-medium">User Dibuat</span>
                <p class="text-lg font-bold text-blue-800 mt-1">{{ $batch->created_rows }}</p>
            </div>
            <div class="bg-purple-50/60 p-4 rounded-xl border border-purple-100">
                <span class="text-xs text-purple-700 font-medium">User Di-update</span>
                <p class="text-lg font-bold text-purple-800 mt-1">{{ $batch->updated_rows }}</p>
            </div>
        </div>
    </div>

    <!-- Error Detail Banner (if invalid_rows > 0) -->
    @if($batch->invalid_rows > 0 || $invalidRows->isNotEmpty())
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2 text-rose-800 font-bold">
                    <span class="material-symbols-outlined text-xl">error</span>
                    <h3>Detail Error Hasil Validasi ({{ $batch->invalid_rows }} Baris Memerlukan Perbaikan)</h3>
                </div>
                <a href="{{ route('admin.users.import.report', $batch) }}" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-md text-xs font-semibold inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">download</span> Download Report XLSX
                </a>
            </div>
            <p class="text-xs text-rose-700 mb-4">Perbaiki file Excel berdasarkan laporan error di bawah ini, lalu upload kembali file yang sudah diperbaiki.</p>

            <div class="max-h-72 overflow-y-auto border border-rose-200 rounded-lg bg-white">
                <table class="w-full text-xs text-left text-slate-700">
                    <thead class="bg-rose-100/60 font-semibold text-rose-900 sticky top-0">
                        <tr>
                            <th class="px-3 py-2">Baris</th>
                            <th class="px-3 py-2">Username</th>
                            <th class="px-3 py-2">Kolom</th>
                            <th class="px-3 py-2">Nilai Input</th>
                            <th class="px-3 py-2">Kode Error</th>
                            <th class="px-3 py-2">Alasan</th>
                            <th class="px-3 py-2">Saran Perbaikan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100">
                        @foreach($invalidRows as $invalidRow)
                            @foreach($invalidRow->errors ?? [] as $err)
                                <tr class="hover:bg-rose-50/50">
                                    <td class="px-3 py-2 font-mono font-bold text-rose-800">{{ $invalidRow->row_number ?: '-' }}</td>
                                    <td class="px-3 py-2 font-mono">{{ $invalidRow->payload['username'] ?? '-' }}</td>
                                    <td class="px-3 py-2 font-semibold text-slate-900">{{ $err['field'] ?? '-' }}</td>
                                    <td class="px-3 py-2 font-mono text-slate-600 max-w-[120px] truncate" title="{{ $err['value'] ?? '' }}">{{ $err['value'] ?? '-' }}</td>
                                    <td class="px-3 py-2 font-mono text-rose-700 font-semibold">{{ $err['code'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-rose-800 font-medium">{{ $err['reason'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $err['suggested_fix'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Row Data Preview Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Preview Data Batch ({{ $rows->total() }} Baris)</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-3 py-2.5"># Row</th>
                        <th class="px-3 py-2.5">Status Row</th>
                        <th class="px-3 py-2.5">Aksi Target</th>
                        <th class="px-3 py-2.5">Username</th>
                        <th class="px-3 py-2.5">Nama</th>
                        <th class="px-3 py-2.5">Email</th>
                        <th class="px-3 py-2.5">Tipe</th>
                        <th class="px-3 py-2.5">NIS</th>
                        <th class="px-3 py-2.5">NIP</th>
                        <th class="px-3 py-2.5">QR Code</th>
                        <th class="px-3 py-2.5">Status User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        @php
                            $payload = $row->payload ?? [];
                            $rowStatusClass = match($row->status) {
                                'valid' => 'bg-emerald-100 text-emerald-800',
                                'invalid' => 'bg-rose-100 text-rose-800',
                                'created' => 'bg-blue-100 text-blue-800',
                                'updated' => 'bg-purple-100 text-purple-800',
                                default => 'bg-slate-100 text-slate-600'
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 {{ $row->status === 'invalid' ? 'bg-rose-50/30' : '' }}">
                            <td class="px-3 py-2.5 font-mono font-bold">{{ $row->row_number }}</td>
                            <td class="px-3 py-2.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $rowStatusClass }}">
                                    {{ strtoupper($row->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 font-semibold text-slate-700 capitalize">{{ $row->action ?? '-' }}</td>
                            <td class="px-3 py-2.5 font-mono font-medium text-slate-900">{{ $payload['username'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 font-medium text-slate-900">{{ $payload['name'] ?? '-' }}</td>
                            <td class="px-3 py-2.5">{{ $payload['email'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 capitalize">{{ $payload['type'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 font-mono">{{ $payload['nis'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 font-mono">{{ $payload['nip'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 font-mono">{{ $payload['qr_code'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 capitalize">{{ $payload['status'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-8 text-center text-slate-400">Belum ada baris data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
