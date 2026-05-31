<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $setting = static::where('group', $group)->where('key', $key)->first();

        if (! $setting || $setting->value === null) {
            return $default;
        }

        return static::decodeValue($group, $key, $setting->value);
    }

    public static function getBool(string $group, string $key, bool $default = false): bool
    {
        $value = static::getValue($group, $key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function setValue(string $group, string $key, mixed $value): self
    {
        $value = $value === '' ? null : $value;

        return static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => static::encodeValue($group, $key, $value)]
        );
    }

    public static function group(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn (self $setting): array => [
                $setting->key => static::decodeValue($group, $setting->key, $setting->value),
            ])
            ->toArray();
    }

    protected static function encodeValue(string $group, string $key, mixed $value): mixed
    {
        if ($value === null || ! static::isSensitive($group, $key)) {
            return $value;
        }

        return Crypt::encryptString((string) $value);
    }

    protected static function decodeValue(string $group, string $key, mixed $value): mixed
    {
        if ($value === null || ! static::isSensitive($group, $key)) {
            return $value;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            return $value;
        }
    }

    protected static function isSensitive(string $group, string $key): bool
    {
        return in_array($group.'.'.$key, [
            'email.password',
            'oauth.google_client_secret',
            'oauth.facebook_client_secret',
        ], true);
    }
}
