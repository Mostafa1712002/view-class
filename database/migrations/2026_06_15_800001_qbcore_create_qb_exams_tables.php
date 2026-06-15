<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #255 — electronic & paper exams linked to the question bank.
 *
 * NEW, module-owned tables prefixed `qb_` so they do NOT collide with the legacy
 * `exams` / `exam_questions` (the teacher class-exam feature) or the #217
 * bankPicker/addFromBank flow. The legacy `exams` table has single NOT NULL FKs
 * (teacher/subject/class/academic_year) + a quiz/midterm enum that cannot express
 * the card's multi-school/grade + paper/electronic + selection-strategy shape, so a
 * separate schema is required (verified against SHOW COLUMNS before building).
 *
 * Snapshot: qb_exam_questions stores a JSON copy of the bank question at add-time,
 * so editing the source bank question never mutates a published exam.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qb_exams')) {
            Schema::create('qb_exams', function (Blueprint $table) {
                $table->id();

                // Multi-tenant scope — nullable for company-wide / super-admin exams.
                $table->unsignedBigInteger('school_id')->nullable()->index();

                $table->string('title');
                $table->text('description')->nullable();

                // electronic (online, auto-graded) | paper (print & distribute)
                $table->string('delivery_type', 20)->default('electronic')->index();

                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('semester_id')->nullable(); // academic_terms.id

                // Scheduling
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->integer('duration_minutes')->nullable();

                // Question selection strategy: manual | random
                $table->string('selection_strategy', 20)->default('manual');
                $table->integer('questions_target')->nullable(); // for random
                $table->json('difficulty_distribution')->nullable(); // {easy,medium,hard}

                // Behaviour flags
                $table->boolean('allow_direct_access')->default(true);
                $table->boolean('show_result_immediately')->default(false);
                $table->boolean('allow_retake')->default(false);
                $table->boolean('shuffle_questions')->default(false);
                $table->boolean('shuffle_answers')->default(false);
                $table->decimal('pass_score', 6, 2)->nullable();

                // Lifecycle: draft | published | stopped
                $table->string('status', 20)->default('draft')->index();
                $table->boolean('is_published')->default(false);

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('qb_exam_targets')) {
            Schema::create('qb_exam_targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('qb_exam_id')->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->integer('grade_level')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('qb_exam_questions')) {
            Schema::create('qb_exam_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('qb_exam_id')->index();

                // FK to the source bank question (null if it is later hard-deleted).
                $table->unsignedBigInteger('bank_question_id')->nullable()->index();

                // Snapshot — frozen copy of the question at add-time.
                $table->string('question_type', 30);
                $table->text('body')->nullable();
                $table->string('attachment_path')->nullable();
                $table->json('answer_snapshot')->nullable(); // options/correct/etc.
                $table->json('question_snapshot')->nullable(); // full row snapshot for audit
                $table->decimal('marks', 6, 2)->default(1);
                $table->integer('sort_order')->default(0);

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qb_exam_questions');
        Schema::dropIfExists('qb_exam_targets');
        Schema::dropIfExists('qb_exams');
    }
};
