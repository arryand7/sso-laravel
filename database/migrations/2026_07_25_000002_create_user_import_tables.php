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
        Schema::create('user_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('template_version')->nullable();
            $table->string('mode')->default('create_only'); // create_only, update_only, create_and_update
            $table->string('status')->default('uploaded');   // uploaded, validating, validation_failed, ready, importing, completed, failed, cancelled
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('created_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('source_file_hash')->nullable();
            $table->string('report_path')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('uploaded_by');
        });

        Schema::create('user_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('user_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('identifier')->nullable(); // username or user_id from the row
            $table->json('payload');                   // raw row data as JSON
            $table->string('action')->nullable();      // create, update, skip
            $table->string('status')->default('pending'); // pending, valid, invalid, created, updated, failed
            $table->json('errors')->nullable();        // array of error objects
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index(['batch_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_import_rows');
        Schema::dropIfExists('user_import_batches');
    }
};
