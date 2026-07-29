<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPhotoImportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'original_filename',
        'temporary_path',
        'detected_extension',
        'detected_mime',
        'identifier_type',
        'identifier',
        'user_id',
        'status',
        'planned_action',
        'error_code',
        'error_message',
        'old_photo_path',
        'new_photo_path',
        'input_size',
        'output_size',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    // ========== Relationships ==========

    public function batch(): BelongsTo
    {
        return $this->belongsTo(UserPhotoImportBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ========== Helper Methods ==========

    public function isImportable(): bool
    {
        return in_array($this->status, ['MATCHED_NEW', 'MATCHED_REPLACE'], true);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'MATCHED_NEW' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'MATCHED_REPLACE' => 'bg-blue-50 text-blue-700 border-blue-200',
            'SKIPPED_EXISTING' => 'bg-slate-100 text-slate-700 border-slate-200',
            'USER_NOT_FOUND' => 'bg-amber-50 text-amber-700 border-amber-200',
            'DUPLICATE_FILE_IDENTIFIER', 'DUPLICATE_USER_IDENTIFIER' => 'bg-orange-50 text-orange-700 border-orange-200',
            'COMPLETED' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            default => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'MATCHED_NEW' => 'Siap Import (Foto Baru)',
            'MATCHED_REPLACE' => 'Siap Ganti Foto Existing',
            'SKIPPED_EXISTING' => 'Dilewati (Sudah Ada Foto)',
            'USER_NOT_FOUND' => 'User Tidak Ditemukan',
            'DUPLICATE_FILE_IDENTIFIER' => 'Konflik File Duplikat',
            'DUPLICATE_USER_IDENTIFIER' => 'Konflik User Duplikat',
            'INVALID_FILENAME' => 'Nama File Tidak Valid',
            'UNSUPPORTED_FORMAT' => 'Format Tidak Didukung',
            'CORRUPTED_IMAGE' => 'Gambar Rusak / Tidak Valid',
            'FILE_TOO_LARGE' => 'Ukuran File Terlalu Besar',
            'IMAGE_DIMENSION_TOO_LARGE' => 'Dimensi Gambar Terlalu Besar',
            'SECURITY_REJECTED' => 'Ditolak Keamanan',
            'PROCESSING_FAILED' => 'Gagal Memproses Gambar',
            'COMPLETED' => 'Berhasil Diimport',
            default => $this->status,
        };
    }
}
