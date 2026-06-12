<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card #215 — Phase 1 schema foundation.
 * Additive-only: adds columns for question codes, content type, curriculum
 * linkage, source tracking, review workflow, import tracking, and external
 * platform sync. All new columns are nullable or have safe defaults so
 * existing rows are unaffected.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bank_questions', function (Blueprint $table) {
            // --- Question code (searchable, esp. for full-image questions) ---
            if (! Schema::hasColumn('bank_questions', 'question_code')) {
                $table->string('question_code', 60)->nullable()->after('question_bank_id');
                $table->index('question_code');
            }

            // --- Content type flag ---
            if (! Schema::hasColumn('bank_questions', 'question_content_type')) {
                $table->string('question_content_type', 10)->notNull()->default('text')->after('question_code');
                // Values: text | image | mixed
            }
            if (! Schema::hasColumn('bank_questions', 'is_full_image_question')) {
                $table->tinyInteger('is_full_image_question')->notNull()->default(0)->after('question_content_type');
                // 1 = the question body IS the image (attachment_path), no text body
            }

            // --- Curriculum linkage (no FK — referenced tables may not exist yet) ---
            if (! Schema::hasColumn('bank_questions', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('lesson_id');
                $table->index('unit_id');
            }
            if (! Schema::hasColumn('bank_questions', 'week_id')) {
                $table->unsignedBigInteger('week_id')->nullable()->after('unit_id');
                $table->index('week_id');
            }
            if (! Schema::hasColumn('bank_questions', 'skill_id')) {
                $table->unsignedBigInteger('skill_id')->nullable()->after('week_id');
                $table->index('skill_id');
            }
            if (! Schema::hasColumn('bank_questions', 'standard_id')) {
                $table->unsignedBigInteger('standard_id')->nullable()->after('skill_id');
                $table->index('standard_id');
            }
            if (! Schema::hasColumn('bank_questions', 'domain_id')) {
                $table->unsignedBigInteger('domain_id')->nullable()->after('standard_id');
                $table->index('domain_id');
            }

            // --- Source / origin ---
            if (! Schema::hasColumn('bank_questions', 'source')) {
                // manual | imported | external_alawwal | external_ana_qudurat
                $table->string('source', 30)->nullable()->after('attachment_path');
            }

            // --- Explanation / hint ---
            if (! Schema::hasColumn('bank_questions', 'explanation')) {
                $table->text('explanation')->nullable()->after('source');
            }

            // --- Review workflow fields ---
            if (! Schema::hasColumn('bank_questions', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('created_by');
                $table->index('reviewed_by');
            }
            if (! Schema::hasColumn('bank_questions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('bank_questions', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('reviewed_at');
            }

            // --- Import tracking ---
            if (! Schema::hasColumn('bank_questions', 'imported_by')) {
                $table->unsignedBigInteger('imported_by')->nullable()->after('rejected_reason');
                $table->index('imported_by');
            }
            if (! Schema::hasColumn('bank_questions', 'import_batch_id')) {
                $table->unsignedBigInteger('import_batch_id')->nullable()->after('imported_by');
                $table->index('import_batch_id');
            }

            // --- External platform sync ---
            if (! Schema::hasColumn('bank_questions', 'external_platform')) {
                $table->string('external_platform', 30)->nullable()->after('import_batch_id');
            }
            if (! Schema::hasColumn('bank_questions', 'external_id')) {
                $table->string('external_id', 100)->nullable()->after('external_platform');
            }
            if (! Schema::hasColumn('bank_questions', 'sync_status')) {
                $table->string('sync_status', 20)->nullable()->after('external_id');
            }
            if (! Schema::hasColumn('bank_questions', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('sync_status');
            }

            // --- Flexible metadata bag ---
            if (! Schema::hasColumn('bank_questions', 'metadata')) {
                $table->json('metadata')->nullable()->after('last_synced_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_questions', function (Blueprint $table) {
            $dropColumns = [
                'question_code',
                'question_content_type',
                'is_full_image_question',
                'unit_id',
                'week_id',
                'skill_id',
                'standard_id',
                'domain_id',
                'source',
                'explanation',
                'reviewed_by',
                'reviewed_at',
                'rejected_reason',
                'imported_by',
                'import_batch_id',
                'external_platform',
                'external_id',
                'sync_status',
                'last_synced_at',
                'metadata',
            ];

            $indexColumns = [
                'question_code',
                'unit_id',
                'week_id',
                'skill_id',
                'standard_id',
                'domain_id',
                'reviewed_by',
                'imported_by',
                'import_batch_id',
            ];

            foreach ($indexColumns as $col) {
                if (Schema::hasColumn('bank_questions', $col)) {
                    $table->dropIndex(['bank_questions_'.$col.'_index']);
                }
            }

            $existing = array_filter(
                $dropColumns,
                fn ($col) => Schema::hasColumn('bank_questions', $col)
            );

            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};
