<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card #215 — Phase 1: question import batch archive tables.
 * Mirrors the student_imports pattern: one header row per upload,
 * granular per-row errors in a child table.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('question_import_batches')) {
            Schema::create('question_import_batches', function (Blueprint $table) {
                $table->id();

                // Bank the import belongs to
                $table->unsignedBigInteger('question_bank_id');
                $table->index('question_bank_id');

                // Multi-tenant scope — nullable because company-wide banks have no school
                $table->unsignedBigInteger('school_id')->nullable();
                $table->index('school_id');

                // File info
                $table->string('original_filename');
                $table->string('stored_path')->nullable();

                // Row counters
                $table->integer('total_rows')->default(0);
                $table->integer('imported_rows')->default(0);
                $table->integer('failed_rows')->default(0);

                // Lifecycle: pending → previewed → completed | failed
                $table->string('status', 20)->default('pending');

                // Serialised preview payload (first N rows before confirmation)
                $table->json('preview_data')->nullable();

                // Who triggered the import
                $table->unsignedBigInteger('created_by')->nullable();
                $table->index('created_by');

                $table->timestamps();
            });
        }

        if (! Schema::hasTable('question_import_errors')) {
            Schema::create('question_import_errors', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('import_batch_id');
                $table->index('import_batch_id');

                // 1-based row number from the source file
                $table->integer('row_number');

                // Validation / processing error messages
                $table->json('errors');

                // Raw cell values from that row (for user-facing error report)
                $table->json('raw');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_errors');
        Schema::dropIfExists('question_import_batches');
    }
};
