<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('applications', 'sync_enabled')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->boolean('sync_enabled')->default(true)->after('is_active');
                $table->json('sync_capabilities')->nullable()->after('sync_enabled');
                $table->integer('api_rate_limit')->default(60)->after('sync_capabilities');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('applications', 'sync_enabled')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn(['sync_enabled', 'sync_capabilities', 'api_rate_limit']);
            });
        }
    }
};
