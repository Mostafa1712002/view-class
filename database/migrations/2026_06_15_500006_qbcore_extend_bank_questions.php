<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#258): the #258 `questions` table is satisfied by extending
 * the LIVE `bank_questions` table (it already has week/skill/standard/domain/code/
 * content_type/status). Add the missing taxonomy columns additively + the
 * question_category dimension (normal / tahsili / passage). All nullable so the live
 * create/edit/import flow is unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_questions', 'question_category')) {
                $table->enum('question_category', ['normal', 'tahsili', 'passage'])
                    ->default('normal')->after('question_code')->index();
            }
            if (! Schema::hasColumn('bank_questions', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('question_bank_id')->index();
            }
            if (! Schema::hasColumn('bank_questions', 'grade_id')) {
                $table->unsignedBigInteger('grade_id')->nullable()->after('subject_id')->index(); // loose grade-level
            }
            if (! Schema::hasColumn('bank_questions', 'class_id')) {
                $table->unsignedBigInteger('class_id')->nullable()->after('grade_id')->index();
            }
            if (! Schema::hasColumn('bank_questions', 'semester_id')) {
                $table->unsignedBigInteger('semester_id')->nullable()->after('class_id')->index(); // → academic_terms
            }
            if (! Schema::hasColumn('bank_questions', 'passage_id')) {
                $table->unsignedBigInteger('passage_id')->nullable()->after('semester_id')->index();
            }
            if (! Schema::hasColumn('bank_questions', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_questions', function (Blueprint $table) {
            foreach ([
                'question_category', 'subject_id', 'grade_id', 'class_id',
                'semester_id', 'passage_id', 'archived_at',
            ] as $col) {
                if (Schema::hasColumn('bank_questions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
