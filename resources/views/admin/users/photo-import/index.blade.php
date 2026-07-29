@extends('layouts.admin')

@section('page-title', 'Bulk Import Foto User via ZIP')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header & Information Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Bulk Import Foto User via ZIP</h2>
                <p class="text-sm text-slate-500 mt-1">Upload banyak foto profil sekaligus dalam format ZIP. Foto dicocokkan ke user secara otomatis berdasarkan NIS atau NIP.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300 rounded-lg text-sm font-semibold transition-colors flex-shrink-0">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                Kembali ke Daftar User
            </a>
        </div>

        @if ($errors->any())
            <div class="mt-6 p-4 bg-rose-50 border border-rose-200 rounded-xl">
                <div class="flex items-center gap-2 text-rose-800 font-semibold text-sm mb-2">
                    <span class="material-symbols-outlined text-[20px]">error</span>
                    Terjadi Kesalahan Upload
                </div>
                <ul class="list-disc list-inside text-xs text-rose-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.photo-import.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <!-- 1. Matching Type Selection -->
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">1. Metode Pencocokan Foto *</label>
                <p class="text-xs text-slate-500 mb-3">Tentukan identifier pengguna yang akan dicocokkan dengan nama file foto di dalam ZIP.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40 transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">NIS — Siswa (Students)</span>
                            <input type="radio" name="matching_type" value="nis" {{ old('matching_type', 'nis') === 'nis' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        </div>
                        <p class="text-xs text-slate-500 leading-normal">Pencocokan khusus akun tipe Siswa. Nama file foto harus sama persis dengan kolom NIS user (contoh: <code>22001001.jpg</code>).</p>
                    </label>

                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40 transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">NIP — Guru & Staff (Teachers & Staff)</span>
                            <input type="radio" name="matching_type" value="nip" {{ old('matching_type') === 'nip' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        </div>
                        <p class="text-xs text-slate-500 leading-normal">Pencocokan khusus akun tipe Guru dan Staff. Nama file foto harus sama persis dengan kolom NIP user (contoh: <code>198707012010011001.jpg</code>).</p>
                    </label>
                </div>
            </div>

            <!-- 2. Existing Photo Policy -->
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">2. Kebijakan Foto Existing *</label>
                <p class="text-xs text-slate-500 mb-3">Tentukan tindakan jika user yang cocok sudah memiliki foto profil.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40 transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Skip Existing Photo (Default & Aman)</span>
                            <input type="radio" name="existing_photo_policy" value="skip" {{ old('existing_photo_policy', 'skip') === 'skip' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        </div>
                        <p class="text-xs text-slate-500 leading-normal">Foto user yang sudah ada tidak akan diubah atau diganti. Hanya meng-upload foto bagi user yang belum memiliki foto profil.</p>
                    </label>

                    <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40 transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">Replace Existing Photo</span>
                            <input type="radio" name="existing_photo_policy" value="replace" {{ old('existing_photo_policy') === 'replace' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        </div>
                        <p class="text-xs text-slate-500 leading-normal">Foto user yang sudah ada akan diganti dengan foto baru di dalam file ZIP.</p>
                    </label>
                </div>
            </div>

            <!-- 3. ZIP File Upload -->
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">3. Upload File ZIP *</label>
                <div class="border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-xl p-8 text-center bg-slate-50/50 transition-colors cursor-pointer" onclick="document.getElementById('zip-input').click()">
                    <span class="material-symbols-outlined text-[48px] text-slate-400 mb-2">folder_zip</span>
                    <p class="text-sm font-semibold text-slate-700">Klik untuk memilih file ZIP foto user</p>
                    <p class="text-xs text-slate-500 mt-1">Menerima hanya format <code>.zip</code> (Maksimal 500 MB & 2.000 file foto)</p>
                    <input type="file" name="file" accept=".zip" required id="zip-input" class="hidden" onchange="updateFileName(this)">
                    <div id="file-name-display" class="mt-3 text-xs font-semibold text-blue-700 hidden"></div>
                </div>
            </div>

            <!-- Guidelines Callout -->
            <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl text-xs text-amber-900 space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-amber-900">
                    <span class="material-symbols-outlined text-[18px]">info</span>
                    Ketentuan Nama File & Format Foto:
                </div>
                <ul class="list-disc list-inside space-y-1 text-amber-800 leading-relaxed">
                    <li>Ubah nama setiap file foto menjadi NIS atau NIP exact user (contoh: <code>22001001.jpg</code> atau <code>198707012010011001.png</code>).</li>
                    <li>Jangan menambahkan teks tambahan seperti <code>Ahmad-22001001.jpg</code> atau <code>22001001 (1).jpg</code>.</li>
                    <li>Format gambar yang didukung: <strong>JPG, JPEG, PNG, WebP</strong>.</li>
                    <li>Foto akan otomatis disesuaikan ke bingkai canvas 4:3 (800×600 px) dengan metode contain (tanpa crop/stretch) dan dikompres hingga ~300 KB.</li>
                </ul>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-sm transition-colors">
                    <span class="material-symbols-outlined text-[20px]">search_check</span>
                    Upload & Inspect Preview
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Import Batches Table -->
    @if (isset($recentBatches) && $recentBatches->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Riwayat & Batch Import Aktif</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar batch import foto yang sedang berjalan atau telah diselesaikan sebelumnya.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <th class="p-3">Batch ID</th>
                            <th class="p-3">Nama File ZIP</th>
                            <th class="p-3">Metode</th>
                            <th class="p-3">Kebijakan</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Progress Foto</th>
                            <th class="p-3">Waktu</th>
                            <th class="p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($recentBatches as $b)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="p-3 font-mono font-bold text-slate-900">#{{ $b->id }}</td>
                                <td class="p-3 font-semibold text-slate-800">{{ $b->original_filename }}</td>
                                <td class="p-3 uppercase font-bold text-blue-700">{{ $b->matching_type }}</td>
                                <td class="p-3 text-slate-600">{{ $b->policy_label }}</td>
                                <td class="p-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border 
                                        {{ $b->status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' }}
                                        {{ $b->status === 'importing' ? 'bg-blue-50 text-blue-700 border-blue-200 animate-pulse' : '' }}
                                        {{ $b->status === 'preview_ready' ? 'bg-purple-50 text-purple-700 border-purple-200' : '' }}
                                        {{ in_array($b->status, ['failed', 'cancelled']) ? 'bg-rose-50 text-rose-700 border-rose-200' : '' }}
                                        {{ !in_array($b->status, ['completed', 'importing', 'preview_ready', 'failed', 'cancelled']) ? 'bg-slate-100 text-slate-700 border-slate-200' : '' }}">
                                        {{ $b->status_label }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono">
                                    @php
                                        $tot = $b->ready_new_count + $b->ready_replace_count;
                                    @endphp
                                    {{ $b->processed_count }} / {{ $tot }} foto
                                    @if ($tot > 0)
                                        <span class="text-slate-400">({{ (int) round(($b->processed_count / $tot) * 100) }}%)</span>
                                    @endif
                                </td>
                                <td class="p-3 text-slate-500">{{ $b->created_at->diffForHumans() }}</td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('admin.users.photo-import.show', $b) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-semibold transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
function updateFileName(input) {
    const display = document.getElementById('file-name-display');
    if (input.files && input.files[0]) {
        display.textContent = 'File terpilih: ' + input.files[0].name + ' (' + (input.files[0].size / (1024 * 1024)).toFixed(2) + ' MB)';
        display.classList.remove('hidden');
    } else {
        display.classList.add('hidden');
    }
}
</script>
@endsection
