<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#258): question_bank_assignments — target a bank to a
 * compound/school/grade/class/teacher. grade_id loose (no hard FK, grade-level lives
 * as classes.grade_level).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_bank_assignments')) {
            return;
        }

        Schema::create('question_bank_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_bank_id')->index();
            $table->unsignedBigInteger('compound_id')->nullable()->index();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('grade_id')->nullable()->index();
            $table->unsignedBigInteger('class_id')->nullable()->index();
            $table->unsignedBigInteger('teacher_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_assignments');
    }
};
