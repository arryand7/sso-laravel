<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'username',
        'email',
        'email_verified_at',
        'password',
        'type',
        'nis',
        'nip',
        'status',
        'last_login_at',
        'photo_path',
        'qr_code',
    ];

    /**
     * Boot model events.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ========== Relationships ==========

    public function loginLogs()
    {
        return $this->hasMany(LoginLog::class);
    }

    public function applicationAccesses()
    {
        return $this->hasMany(UserApplicationAccess::class);
    }

    public function syncStatuses()
    {
        return $this->hasMany(ApplicationUserSyncStatus::class);
    }

    public function assignedApplications()
    {
        return $this->belongsToMany(Application::class, 'user_application_accesses')
            ->withPivot(['application_role', 'status', 'granted_at', 'granted_by', 'revoked_at', 'revoked_by', 'last_synced_at'])
            ->withTimestamps();
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ========== Accessors ==========

    public function getIdentifierAttribute(): string
    {
        return $this->nis ?? $this->nip ?? $this->username;
    }

    /**
     * Get the full URL to the processed profile photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return asset('storage/'.ltrim($this->photo_path, '/'));
    }

    /**
     * Get the avatar URL: photo if available, otherwise a fallback initial avatar.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->photo_url) {
            return $this->photo_url;
        }

        $initials = collect(explode(' ', $this->name))
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return 'https://ui-avatars.com/api/?name='.urlencode($initials).'&size=200&background=2463eb&color=ffffff&bold=true';
    }

    /**
     * Get masked QR code for display in listings.
     */
    public function getMaskedQrCodeAttribute(): ?string
    {
        if (! $this->qr_code) {
            return null;
        }

        $len = mb_strlen($this->qr_code);
        if ($len <= 4) {
            return $this->qr_code;
        }

        return str_repeat('•', $len - 4).mb_substr($this->qr_code, -4);
    }

    // ========== Mutators ==========

    /**
     * Trim and convert empty QR code strings to null.
     */
    public function setQrCodeAttribute(?string $value): void
    {
        $value = $value !== null ? trim(preg_replace('/[\r\n]+/', '', $value)) : null;
        $this->attributes['qr_code'] = $value !== '' ? $value : null;
    }

    // ========== Methods ==========

    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'superadmin']);
    }

    public function canAccessApplication(Application $app): bool
    {
        return $app->roles()
            ->whereIn('roles.id', $this->roles->pluck('id'))
            ->exists();
    }

    /**
     * Get accessible applications for portal dashboard
     */
    public function accessibleApplications()
    {
        $roleIds = $this->roles->pluck('id');

        return Application::where('is_active', true)
            ->whereHas('roles', function ($query) use ($roleIds) {
                $query->whereIn('roles.id', $roleIds);
            })
            ->get();
    }

    /**
     * Build OIDC claims for id_token
     */
    public function getOidcClaims(): array
    {
        return [
            'sub' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'roles' => $this->roles->pluck('name')->toArray(),
            'nis' => $this->nis,
            'nip' => $this->nip,
        ];
    }
}
