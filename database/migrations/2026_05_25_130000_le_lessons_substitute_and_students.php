<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card "تعديلات الحصص": extend the lessons (schedule_periods) flow with
 *  - a substitute teacher slot (المعلم البديل) that keeps the primary teacher.
 *  - a pivot linking specific students to a lesson (إدارة الطلاب داخل الحصة).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_periods') && !Schema::hasColumn('schedule_periods', 'substitute_teacher_id')) {
            Schema::table('schedule_periods', function (Blueprint $table) {
                $table->foreignId('substitute_teacher_id')
                    ->nullable()
                    ->after('teacher_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('lesson_students')) {
            Schema::create('lesson_students', function (Blueprint $table) {
                $table->id();
                $table->foreignId('schedule_period_id')->constrained('schedule_periods')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['schedule_period_id', 'student_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_students');

        if (Schema::hasTable('schedule_periods') && Schema::hasColumn('schedule_periods', 'substitute_teacher_id')) {
            Schema::table('schedule_periods', function (Blueprint $table) {
                $table->dropConstrainedForeignId('substitute_teacher_id');
            });
        }
    }
};
