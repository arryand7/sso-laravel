<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Encrypt existing plaintext setting values that contain service secrets.
     */
    public function up(): void
    {
        $sensitive = [
            ['group' => 'email', 'key' => 'password'],
            ['group' => 'oauth', 'key' => 'google_client_secret'],
            ['group' => 'oauth', 'key' => 'facebook_client_secret'],
        ];

        foreach ($sensitive as $setting) {
            DB::table('settings')
                ->where('group', $setting['group'])
                ->where('key', $setting['key'])
                ->whereNotNull('value')
                ->orderBy('id')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        if ($row->value === '' || $this->isEncrypted($row->value)) {
                            continue;
                        }

                        DB::table('settings')
                            ->where('id', $row->id)
                            ->update(['value' => Crypt::encryptString($row->value)]);
                    }
                });
        }
    }

    /**
     * Keep secrets encrypted if this migration is rolled back.
     */
    public function down(): void
    {
        //
    }

    protected function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
