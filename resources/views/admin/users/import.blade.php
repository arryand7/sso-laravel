@extends('layouts.admin')

@section('page-title', 'Import User')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Upload Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Import User dari File Excel</h2>
                <p class="text-sm text-slate-500 mt-1">Gunakan template resmi Sabira Connect agar struktur data divalidasi dengan benar sebelum ditulis ke database.</p>
            </div>
            <a href="{{ route('admin.users.import.template') }}" 
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-sm font-semibold transition-colors flex-shrink-0">
                <span class="material-symbols-outlined text-[20px]">download</span>
                Download Template Excel
            </a>
        </div>

        <form method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <!-- Mode Selection -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Mode Import *</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40 transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Create Only</span>
                            <input type="radio" name="mode" value="create_only" checked class="text-blue-600 focus:ring-blue-500">
                        </div>
                        <p class="text-xs text-slate-500 leading-normal">Hanya membuat user baru. Jika user sudah ada, baris dilaporkan sebagai error. (Default & Aman)</p>
                    </label>

                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40 transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Update Only</span>
                            <input type="radio" name="mode" value="update_only" {{ old('mode') === 'update_only' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        </div>
                        <p class="text-xs text-slate-500 leading-normal">Hanya memperbarui user existing. Menggunakan username/user_id sebagai acuan.</p>
                    </label>

                    @if(auth()->user()?->hasRole('superadmin'))
                        <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40 transition-all">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-bold text-slate-900">Create & Update</span>
                                <input type="radio" name="mode" value="create_and_update" {{ old('mode') === 'create_and_update' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                            </div>
                            <p class="text-xs text-slate-500 leading-normal">Buat user baru dan perbarui user existing secara bersamaan. (Superadmin Only)</p>
                        </label>
                    @endif
                </div>
            </div>

            <!-- File Upload -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">File Excel (XLSX) *</label>
                <div class="border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl p-6 text-center bg-slate-50/50 transition-colors">
                    <input type="file" name="file" accept=".xlsx" required id="file-input" class="hidden">
                    <label for="file-input" class="cursor-pointer flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-4xl text-blue-600 mb-2">upload_file</span>
                        <span id="file-label" class="text-sm font-semibold text-slate-700">Pilih file XLSX dari komputer Anda</span>
                        <span class="text-xs text-slate-400 mt-1">Format wajib: XLSX. Maksimal file: 10 MB. Maksimal data: 5.000 baris.</span>
                    </label>
                </div>
                @error('file')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-medium">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">verified</span> Upload & Validasi
                </button>
            </div>
        </form>
    </div>

    <!-- Batch History List -->
    @if(isset($batches) && $batches->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Riwayat Batch Import</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Batch / File</th>
                            <th class="px-4 py-3">Pengupload</th>
                            <th class="px-4 py-3">Mode</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Ringkasan Row</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($batches as $batch)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-slate-900">{{ $batch->original_filename }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ Str::limit($batch->uuid, 13) }}</div>
                                </td>
                                <td class="px-4 py-3.5">{{ $batch->uploader?->name ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-xs">
                                    <span class="px-2 py-1 bg-slate-100 rounded text-slate-700 font-medium">{{ $batch->mode_label }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @php
                                        $statusClasses = match($batch->status) {
                                            'completed' => 'bg-emerald-100 text-emerald-700',
                                            'ready' => 'bg-blue-100 text-blue-700',
                                            'validation_failed', 'failed' => 'bg-rose-100 text-rose-700',
                                            'validating', 'importing' => 'bg-amber-100 text-amber-700 animate-pulse',
                                            'cancelled' => 'bg-slate-100 text-slate-500',
                                            default => 'bg-slate-100 text-slate-600'
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusClasses }}">
                                        {{ $batch->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-xs space-y-0.5">
                                    <div>Total: <span class="font-semibold">{{ $batch->total_rows }}</span></div>
                                    @if($batch->invalid_rows > 0)
                                        <div class="text-rose-600 font-medium">Error: {{ $batch->invalid_rows }}</div>
                                    @endif
                                    @if($batch->completed_at)
                                        <div class="text-emerald-600 font-medium">Dibuat: {{ $batch->created_rows }}, Updated: {{ $batch->updated_rows }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500">{{ $batch->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.users.import.show', $batch) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-md transition-colors">
                                            Review
                                        </a>
                                        @if($batch->invalid_rows > 0 || $batch->status === 'validation_failed')
                                            <a href="{{ route('admin.users.import.report', $batch) }}" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-medium rounded-md border border-rose-200 transition-colors" title="Download Report Excel">
                                                Report
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $batches->links() }}
            </div>
        </div>
    @endif
</div>

<script>
    document.getElementById('file-input')?.addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        const fileLabel = document.getElementById('file-label');
        if (fileName && fileLabel) {
            fileLabel.textContent = `Terpilih: ${fileName}`;
            fileLabel.classList.add('text-blue-600', 'font-bold');
        }
    });
</script>
@endsection
