<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserApplicationAccess extends Model
{
    use HasFactory;

    protected $table = 'user_application_accesses';

    protected $fillable = [
        'user_id',
        'application_id',
        'application_role',
        'status',
        'granted_at',
        'granted_by',
        'revoked_at',
        'revoked_by',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_synced_at' => 'datetime',
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

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function syncStatus()
    {
        return $this->hasOne(ApplicationUserSyncStatus::class, 'user_id', 'user_id')
            ->where('application_id', $this->application_id);
    }

    // ========== Helpers ==========

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }
}
