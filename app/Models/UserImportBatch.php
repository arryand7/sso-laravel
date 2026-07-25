<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserImportBatch extends Model
{
    protected $fillable = [
        'uuid',
        'original_filename',
        'stored_path',
        'template_version',
        'mode',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'created_rows',
        'updated_rows',
        'failed_rows',
        'uploaded_by',
        'source_file_hash',
        'report_path',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ========== Relationships ==========

    public function rows()
    {
        return $this->hasMany(UserImportRow::class, 'batch_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ========== Scopes ==========

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ========== Methods ==========

    public function isEditable(): bool
    {
        return in_array($this->status, ['uploaded', 'validation_failed'], true);
    }

    public function isCommittable(): bool
    {
        return $this->status === 'ready';
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['uploaded', 'validating', 'validation_failed', 'ready'], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled'], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'uploaded' => 'Diupload',
            'validating' => 'Memvalidasi',
            'validation_failed' => 'Validasi Gagal',
            'ready' => 'Siap Import',
            'importing' => 'Mengimport',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    public function getModeLabelAttribute(): string
    {
        return match ($this->mode) {
            'create_only' => 'Buat Baru',
            'update_only' => 'Update Saja',
            'create_and_update' => 'Buat & Update',
            default => $this->mode,
        };
    }
}
