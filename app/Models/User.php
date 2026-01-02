<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'type',
        'nis',
        'nip',
        'status',
        'last_login_at',
    ];

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
