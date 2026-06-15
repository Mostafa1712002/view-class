<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#258): passages (القطعة) + passage_questions link.
 * FK reuse: subject_id → subjects, semester_id → academic_terms, week_id → study_weeks,
 * skill_id → skills.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('passages')) {
            Schema::create('passages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('question_bank_id')->nullable()->index();
                $table->string('passage_code', 60)->nullable()->index();
                $table->text('passage_text')->nullable();
                $table->string('passage_image')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('semester_id')->nullable()->index(); // → academic_terms
                $table->unsignedBigInteger('week_id')->nullable()->index();      // → study_weeks
                $table->unsignedBigInteger('skill_id')->nullable()->index();
                $table->unsignedTinyInteger('difficulty_level')->nullable();
                $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])
                    ->default('approved')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('passage_questions')) {
            Schema::create('passage_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('passage_id')->index();
                $table->unsignedBigInteger('question_id')->index(); // → bank_questions.id
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->unique(['passage_id', 'question_id'], 'passage_question_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('passage_questions');
        Schema::dropIfExists('passages');
    }
};
