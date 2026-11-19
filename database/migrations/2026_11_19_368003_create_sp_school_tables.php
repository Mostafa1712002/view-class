<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sp_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id')->nullable(); // NULL = global row
            $t->string('program', 10);
            $t->json('level_bands')->nullable();
            $t->boolean('allow_school_edit')->default(false);
            $t->json('grade_levels')->nullable();
            $t->unsignedTinyInteger('weeks_count')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'program']);
        });

        Schema::create('sp_teachers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->string('program', 10);
            $t->unsignedBigInteger('sp_subject_id')->nullable(); // NULL = every subject of the program
            $t->unsignedBigInteger('teacher_id');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'sp_subject_id', 'teacher_id'], 'sp_teachers_unique');
            $t->index(['school_id', 'program']);
        });

        Schema::create('sp_groups', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->string('program', 10);
            $t->string('name');
            $t->unsignedBigInteger('teacher_id')->nullable();
            $t->string('target_level', 20)->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'program']);
        });

        Schema::create('sp_enrollments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('school_id');
            $t->string('program', 10);
            $t->unsignedBigInteger('student_id');
            $t->unsignedBigInteger('group_id')->nullable()->index();
            $t->unsignedBigInteger('enrolled_by')->nullable();
            $t->timestamp('enrolled_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'program', 'student_id'], 'sp_enrollments_unique');
            $t->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sp_enrollments');
        Schema::dropIfExists('sp_groups');
        Schema::dropIfExists('sp_teachers');
        Schema::dropIfExists('sp_settings');
    }
};
