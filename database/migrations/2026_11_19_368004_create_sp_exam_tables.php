<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sp_exams', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->string('program', 10);
            $t->string('title');
            $t->enum('type', ['single', 'multi'])->default('single');
            $t->unsignedInteger('expiry_hours');
            $t->dateTime('expires_at');
            $t->unsignedTinyInteger('max_exit_attempts')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->string('creator_role', 20);
            $t->unsignedBigInteger('teacher_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'program', 'deleted_at']);
        });

        Schema::create('sp_exam_targets', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('exam_id')->index();
            $t->unsignedBigInteger('class_id')->nullable()->index();
            $t->unsignedBigInteger('group_id')->nullable()->index();
        });

        Schema::create('sp_exam_subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('exam_id')->index();
            $t->unsignedBigInteger('sp_subject_id');
            $t->unsignedBigInteger('teacher_id')->nullable();
            $t->unsignedSmallInteger('duration_minutes');
            $t->unsignedSmallInteger('total_questions');
            $t->unsignedTinyInteger('order_index')->default(0);
            $t->enum('term', ['first', 'second'])->nullable();
            $t->json('weeks')->nullable();
            $t->json('question_grade_levels')->nullable();
            $t->unsignedTinyInteger('easy_pct')->nullable();
            $t->unsignedTinyInteger('medium_pct')->nullable();
            $t->unsignedTinyInteger('hard_pct')->nullable();
            $t->unsignedInteger('range_start')->nullable();
            $t->unsignedInteger('range_end')->nullable();
            $t->timestamps();
        });

        Schema::create('sp_exam_skill_shares', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('exam_subject_id');
            $t->unsignedBigInteger('sp_skill_id');
            $t->unsignedTinyInteger('percentage');
            $t->unique(['exam_subject_id', 'sp_skill_id'], 'sp_skill_shares_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sp_exam_skill_shares');
        Schema::dropIfExists('sp_exam_subjects');
        Schema::dropIfExists('sp_exam_targets');
        Schema::dropIfExists('sp_exams');
    }
};
