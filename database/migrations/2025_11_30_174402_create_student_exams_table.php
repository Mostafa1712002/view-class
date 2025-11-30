<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->datetime('started_at')->nullable();
            $table->datetime('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'graded'])->default('not_started');
            $table->integer('attempt_number')->default(1);
            $table->text('teacher_feedback')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id', 'attempt_number'], 'student_exam_attempt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_exams');
    }
};
