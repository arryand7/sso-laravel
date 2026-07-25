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
        Schema::create('application_user_sync_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('application_id')
                ->constrained('applications')
                ->onDelete('cascade');
            $table->enum('status', [
                'never_synced',
                'matched',
                'needs_update',
                'missing_in_application',
                'suspended',
                'conflict',
                'failed',
            ])->default('never_synced');
            $table->string('external_user_id')->nullable()->comment('ID internal user pada aplikasi lokal');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_reported_at')->nullable();
            $table->string('last_error_code')->nullable();
            $table->text('last_error_message')->nullable();
            $table->string('local_checksum')->nullable();
            $table->string('gate_checksum')->nullable();
            $table->timestamps();

            // Indexes & Unique Constraints
            $table->unique(['user_id', 'application_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_user_sync_statuses');
    }
};
