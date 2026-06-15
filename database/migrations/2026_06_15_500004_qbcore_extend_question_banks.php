<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#258): extend the LIVE question_banks table additively.
 * All new cols nullable/defaulted so existing rows + the live create/edit flow keep
 * working. The legacy question_bank_subjects pivot (many subjects) is kept; subject_id
 * is the new single-subject primary per #258. semester_id → academic_terms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            if (! Schema::hasColumn('question_banks', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('school_id')->index();
            }
            if (! Schema::hasColumn('question_banks', 'semester_id')) {
                $table->unsignedBigInteger('semester_id')->nullable()->after('subject_id')->index(); // → academic_terms
            }
            if (! Schema::hasColumn('question_banks', 'bank_type')) {
                // عام / خاص
                $table->enum('bank_type', ['public', 'private'])->default('private')->after('semester_id');
            }
            if (! Schema::hasColumn('question_banks', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('bank_type');
            }
            if (! Schema::hasColumn('question_banks', 'allow_excel_import')) {
                $table->boolean('allow_excel_import')->default(true)->after('requires_approval');
            }
            if (! Schema::hasColumn('question_banks', 'allow_images')) {
                $table->boolean('allow_images')->default(true)->after('allow_excel_import');
            }
            if (! Schema::hasColumn('question_banks', 'allow_passage_questions')) {
                $table->boolean('allow_passage_questions')->default(false)->after('allow_images');
            }
            if (! Schema::hasColumn('question_banks', 'allow_tahsili_questions')) {
                $table->boolean('allow_tahsili_questions')->default(false)->after('allow_passage_questions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            foreach ([
                'subject_id', 'semester_id', 'bank_type', 'requires_approval',
                'allow_excel_import', 'allow_images', 'allow_passage_questions',
                'allow_tahsili_questions',
            ] as $col) {
                if (Schema::hasColumn('question_banks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
