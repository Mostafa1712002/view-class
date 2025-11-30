<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('semester');
            $table->decimal('quiz_avg', 5, 2)->nullable();
            $table->decimal('homework_avg', 5, 2)->nullable();
            $table->decimal('midterm', 5, 2)->nullable();
            $table->decimal('final', 5, 2)->nullable();
            $table->decimal('participation', 5, 2)->nullable();
            $table->decimal('total', 5, 2)->nullable();
            $table->string('letter_grade')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year_id', 'semester'], 'grades_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
