<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'client_app',
        'ip_address',
        'user_agent',
        'login_at',
    ];

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
        ];
    }

    // ========== Relationships ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========

    public function scopeFromApp($query, string $app)
    {
        return $query->where('client_app', $app);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('login_at', [$startDate, $endDate]);
    }

    // ========== Static Methods ==========

    /**
     * Record login event
     */
    public static function recordLogin(User $user, string $clientApp, ?string $ip = null, ?string $userAgent = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'client_app' => $clientApp,
            'ip_address' => $ip ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'login_at' => now(),
        ]);
    }
}
