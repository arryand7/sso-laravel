<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\PassportClient;
use Spatie\Permission\Models\Role;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'client_id',
        'client_secret',
        'redirect_uri',
        'sso_login_url',
        'category',
        'icon',
        'logo_path',
        'description',
        'is_active',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    // ========== Relationships ==========

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'application_role')
                    ->withTimestamps();
    }

    public function passportClient()
    {
        return $this->hasOne(PassportClient::class, 'id', 'client_id');
    }

    /**
     * Get all users who can access this application (via roles)
     */
    public function users()
    {
        $roleIds = $this->roles()->pluck('roles.id');
        
        return User::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        });
    }

    /**
     * Get paginated users with search/filter for admin view
     */
    public function getUsersWithAccess(array $filters = [], int $perPage = 15)
    {
        return $this->users()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('nis', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->with('roles')
            ->orderBy('name')
            ->paginate($perPage);
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ========== Methods ==========

    /**
     * Generate OAuth credentials saat create application
     */
    public static function generateCredentials(): array
    {
        return [
            'client_id' => Str::uuid()->toString(),
            'client_secret' => Str::random(64),
        ];
    }

    /**
     * Sync application data to Passport client table.
     */
    public function syncPassportClient(): PassportClient
    {
        $redirectUris = array_values(array_filter(array_map('trim', explode(',', $this->redirect_uri))));

        return PassportClient::updateOrCreate(
            ['id' => $this->client_id],
            [
                'name' => $this->name,
                'secret' => $this->client_secret,
                'provider' => null,
                'redirect_uris' => $redirectUris,
                'grant_types' => ['authorization_code', 'refresh_token'],
                'revoked' => ! $this->is_active,
            ]
        );
    }

    /**
     * Validate redirect URI
     */
    public function isValidRedirectUri(string $uri): bool
    {
        $allowedUris = array_map('trim', explode(',', $this->redirect_uri));
        return in_array($uri, $allowedUris);
    }

    /**
     * Mask client_id for display
     */
    public function getMaskedClientIdAttribute(): string
    {
        return Str::mask($this->client_id, '*', 8, -4);
    }
}
