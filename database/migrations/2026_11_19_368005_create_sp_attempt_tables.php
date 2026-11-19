<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sp_attempts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->string('program', 10);
            $t->unsignedBigInteger('exam_id');
            $t->unsignedBigInteger('exam_subject_id');
            $t->unsignedBigInteger('student_id');
            $t->enum('status', ['in_progress', 'completed', 'timed_out', 'auto_ended'])->default('in_progress');
            $t->dateTime('started_at');
            $t->dateTime('deadline_at');
            $t->dateTime('ended_at')->nullable();
            $t->dateTime('last_seen_at')->nullable();
            $t->unsignedSmallInteger('items_count')->default(0);
            $t->unsignedSmallInteger('answered_count')->default(0);
            $t->unsignedSmallInteger('correct_count')->default(0);
            $t->decimal('score_pct', 5, 2)->nullable();
            $t->unsignedInteger('time_spent_seconds')->default(0);
            $t->unsignedSmallInteger('exit_count')->default(0);
            $t->dateTime('completed_event_at')->nullable();
            $t->timestamps();
            $t->unique(['exam_subject_id', 'student_id'], 'sp_attempts_subject_student');
            $t->index(['school_id', 'program', 'status']);
            $t->index(['student_id', 'program']);
            $t->index('exam_id');
        });

        Schema::create('sp_attempt_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('attempt_id')->index();
            $t->unsignedBigInteger('bank_question_id')->nullable();
            $t->unsignedBigInteger('sp_skill_id')->nullable()->index();
            $t->enum('difficulty', ['easy', 'medium', 'hard'])->nullable();
            $t->unsignedBigInteger('passage_id')->nullable();
            $t->unsignedSmallInteger('sub_index')->default(0);
            $t->unsignedSmallInteger('order_index')->default(0);
            $t->text('stem')->nullable();
            $t->string('image_path')->nullable();
            $t->json('options');
            $t->unsignedTinyInteger('correct_index');
            $t->unsignedTinyInteger('answer_index')->nullable();
            $t->boolean('is_correct')->nullable();
            $t->dateTime('answered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sp_attempt_items');
        Schema::dropIfExists('sp_attempts');
    }
};
