<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#258): extend the LIVE import tables additively with the
 * richer #258 columns. Existing columns kept; new ones nullable/defaulted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('question_import_batches', 'import_type')) {
                // questions | skills (#248 skill import) | passages
                $table->string('import_type', 30)->default('questions')->after('question_bank_id');
            }
            if (! Schema::hasColumn('question_import_batches', 'images_zip_path')) {
                $table->string('images_zip_path')->nullable()->after('stored_path');
            }
            if (! Schema::hasColumn('question_import_batches', 'valid_rows')) {
                $table->integer('valid_rows')->default(0)->after('total_rows');
            }
            if (! Schema::hasColumn('question_import_batches', 'invalid_rows')) {
                $table->integer('invalid_rows')->default(0)->after('valid_rows');
            }
            if (! Schema::hasColumn('question_import_batches', 'imported_by')) {
                $table->unsignedBigInteger('imported_by')->nullable()->after('created_by')->index();
            }
            if (! Schema::hasColumn('question_import_batches', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('question_import_batches', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('question_import_batches', 'error_report_path')) {
                $table->string('error_report_path')->nullable()->after('finished_at');
            }
            if (! Schema::hasColumn('question_import_batches', 'settings')) {
                $table->json('settings')->nullable()->after('error_report_path');
            }
        });

        Schema::table('question_import_errors', function (Blueprint $table) {
            if (! Schema::hasColumn('question_import_errors', 'question_code')) {
                $table->string('question_code', 60)->nullable()->after('row_number');
            }
            if (! Schema::hasColumn('question_import_errors', 'error_field')) {
                $table->string('error_field')->nullable()->after('question_code');
            }
            if (! Schema::hasColumn('question_import_errors', 'error_message')) {
                $table->text('error_message')->nullable()->after('error_field');
            }
            if (! Schema::hasColumn('question_import_errors', 'error_type')) {
                $table->string('error_type', 40)->nullable()->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_import_batches', function (Blueprint $table) {
            foreach ([
                'import_type', 'images_zip_path', 'valid_rows', 'invalid_rows',
                'imported_by', 'started_at', 'finished_at', 'error_report_path', 'settings',
            ] as $col) {
                if (Schema::hasColumn('question_import_batches', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('question_import_errors', function (Blueprint $table) {
            foreach (['question_code', 'error_field', 'error_message', 'error_type'] as $col) {
                if (Schema::hasColumn('question_import_errors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
