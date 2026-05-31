<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_settings_are_encrypted_at_rest(): void
    {
        Setting::setValue('oauth', 'google_client_secret', 'secret-value');

        $stored = Setting::where('group', 'oauth')
            ->where('key', 'google_client_secret')
            ->value('value');

        $this->assertNotSame('secret-value', $stored);
        $this->assertSame('secret-value', Setting::getValue('oauth', 'google_client_secret'));
        $this->assertSame('secret-value', Setting::group('oauth')['google_client_secret']);
    }

    public function test_existing_plaintext_sensitive_settings_are_still_readable(): void
    {
        Setting::query()->create([
            'group' => 'email',
            'key' => 'password',
            'value' => 'plain-old-value',
        ]);

        $this->assertSame('plain-old-value', Setting::getValue('email', 'password'));
    }

    public function test_sensitive_settings_backfill_migration_encrypts_plaintext_values(): void
    {
        Setting::query()->create([
            'group' => 'oauth',
            'key' => 'google_client_secret',
            'value' => 'plain-google-secret',
        ]);
        Setting::query()->create([
            'group' => 'app',
            'key' => 'timezone',
            'value' => 'Asia/Jakarta',
        ]);

        $migration = require database_path('migrations/2026_05_31_000000_encrypt_sensitive_settings.php');
        $migration->up();

        $secret = Setting::where('group', 'oauth')
            ->where('key', 'google_client_secret')
            ->value('value');
        $timezone = Setting::where('group', 'app')
            ->where('key', 'timezone')
            ->value('value');

        $this->assertNotSame('plain-google-secret', $secret);
        $this->assertSame('plain-google-secret', Crypt::decryptString($secret));
        $this->assertSame('plain-google-secret', Setting::getValue('oauth', 'google_client_secret'));
        $this->assertSame('Asia/Jakarta', $timezone);
    }
}
