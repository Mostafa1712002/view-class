<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * === Anti-cheat card (ac) ===
 * Log of exit / focus-loss attempts a student makes while sitting an exam.
 * Surfaced to admins inside the exam results report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_exit_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_exam_id')->constrained('student_exams')->cascadeOnDelete();
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('student_id')->index();

            // tab_hidden | window_blur | beforeunload | multi_tab | back_navigation
            $table->string('attempt_type', 32);
            // running counter (per student_exam) at the moment this row was written
            $table->unsignedInteger('attempt_count')->default(1);
            // did this attempt cross the threshold and auto-end the exam?
            $table->boolean('auto_ended')->default(false);

            $table->string('device', 64)->nullable();   // Desktop / Mobile / Tablet
            $table->string('browser', 64)->nullable();   // Chrome / Firefox / Safari ...
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_exit_attempts');
    }
};
