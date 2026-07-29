<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPhotoImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'matching_type',
        'existing_photo_policy',
        'original_filename',
        'stored_zip_path',
        'status',
        'total_entries',
        'total_photo_files',
        'ready_new_count',
        'ready_replace_count',
        'skipped_count',
        'failed_count',
        'processed_count',
        'uploaded_by',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ========== Relationships ==========

    public function items(): HasMany
    {
        return $this->hasMany(UserPhotoImportItem::class, 'batch_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ========== Helper Methods ==========

    public function isCommittable(): bool
    {
        return $this->status === 'preview_ready';
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['uploaded', 'inspecting', 'preview_ready'], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'completed_with_errors', 'failed', 'cancelled', 'expired'], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'uploaded' => 'Diupload',
            'inspecting' => 'Memeriksa File ZIP',
            'preview_ready' => 'Preview Siap',
            'importing' => 'Memproses Import',
            'completed' => 'Selesai',
            'completed_with_errors' => 'Selesai dengan Catatan',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kedaluwarsa',
            default => $this->status,
        };
    }

    public function getMatchingTypeLabelAttribute(): string
    {
        return match ($this->matching_type) {
            'nis' => 'NIS (Siswa)',
            'nip' => 'NIP (Guru & Staff)',
            default => strtoupper($this->matching_type),
        };
    }

    public function getPolicyLabelAttribute(): string
    {
        return match ($this->existing_photo_policy) {
            'skip' => 'Lewati Foto Existing',
            'replace' => 'Ganti Foto Existing',
            default => $this->existing_photo_policy,
        };
    }
}
