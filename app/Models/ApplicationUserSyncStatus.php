<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationUserSyncStatus extends Model
{
    use HasFactory;

    protected $table = 'application_user_sync_statuses';

    protected $fillable = [
        'user_id',
        'application_id',
        'status',
        'external_user_id',
        'last_sync_at',
        'last_reported_at',
        'last_error_code',
        'last_error_message',
        'local_checksum',
        'gate_checksum',
    ];

    protected function casts(): array
    {
        return [
            'last_sync_at' => 'datetime',
            'last_reported_at' => 'datetime',
        ];
    }

    // ========== Relationships ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    // ========== Accessors & Helpers ==========

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'matched' => 'Matched',
            'needs_update' => 'Needs Update',
            'missing_in_application' => 'Missing in Application',
            'suspended' => 'Suspended in App',
            'conflict' => 'Conflict',
            'failed' => 'Failed',
            default => 'Never Synced',
        };
    }
}
