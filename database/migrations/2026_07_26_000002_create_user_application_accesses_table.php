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
        Schema::create('user_application_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('application_id')
                ->constrained('applications')
                ->onDelete('cascade');
            $table->string('application_role')->nullable()
                ->comment('Role khusus user pada aplikasi tujuan (misal: santri, guru, wali)');
            $table->enum('status', ['active', 'inactive', 'revoked'])
                ->default('active')
                ->comment('Status hak akses user pada aplikasi');
            $table->timestamp('granted_at')->nullable();
            $table->foreignId('granted_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Indexes & Unique Constraints
            $table->unique(['user_id', 'application_id']);
            $table->index('status');
            $table->index('application_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_application_accesses');
    }
};
