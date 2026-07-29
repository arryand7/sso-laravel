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
        Schema::create('user_photo_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('matching_type'); // 'nis', 'nip'
            $table->string('existing_photo_policy')->default('skip'); // 'skip', 'replace'
            $table->string('original_filename');
            $table->string('stored_zip_path');
            $table->string('status')->default('uploaded');
            // Statuses: uploaded, inspecting, preview_ready, importing, completed, completed_with_errors, failed, cancelled, expired

            $table->unsignedInteger('total_entries')->default(0);
            $table->unsignedInteger('total_photo_files')->default(0);
            $table->unsignedInteger('ready_new_count')->default(0);
            $table->unsignedInteger('ready_replace_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);

            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('uploaded_by');
        });

        Schema::create('user_photo_import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('user_photo_import_batches')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('temporary_path');
            $table->string('detected_extension')->nullable();
            $table->string('detected_mime')->nullable();
            $table->string('identifier_type'); // 'nis', 'nip'
            $table->string('identifier'); // exact string identifier
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('pending');
            // Statuses: MATCHED_NEW, MATCHED_REPLACE, SKIPPED_EXISTING, USER_NOT_FOUND, DUPLICATE_FILE_IDENTIFIER, DUPLICATE_USER_IDENTIFIER, INVALID_FILENAME, UNSUPPORTED_FORMAT, CORRUPTED_IMAGE, FILE_TOO_LARGE, IMAGE_DIMENSION_TOO_LARGE, SECURITY_REJECTED, PROCESSING_FAILED, COMPLETED

            $table->string('planned_action')->default('none'); // 'import', 'replace', 'none'
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->string('old_photo_path')->nullable();
            $table->string('new_photo_path')->nullable();
            $table->unsignedBigInteger('input_size')->nullable();
            $table->unsignedBigInteger('output_size')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index(['batch_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_photo_import_items');
        Schema::dropIfExists('user_photo_import_batches');
    }
};
