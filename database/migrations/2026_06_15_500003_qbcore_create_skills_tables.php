<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#248): skills (المهارات) + skill assignments.
 *
 * FK reuse: subject_id → subjects, semester_id → academic_terms, week_id → study_weeks.
 * grade_id is stored loose (nullable, NO hard FK) because grade-level lives as
 * classes.grade_level (int), not as a dedicated grades table (which is the gradebook).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('skills')) {
            Schema::create('skills', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('name');
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('semester_id')->nullable()->index();   // → academic_terms
                $table->unsignedBigInteger('week_id')->nullable()->index();        // → study_weeks
                // عادية / قدرات / تحصيلي / لفظي / كمي
                $table->enum('skill_type', ['normal', 'ability', 'tahsili', 'verbal', 'quantitative'])
                    ->default('normal')->index();
                $table->boolean('is_tahsili')->default(false);
                $table->boolean('is_ability')->default(false);
                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('skill_assignments')) {
            Schema::create('skill_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('skill_id')->index();
                $table->unsignedBigInteger('compound_id')->nullable()->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('grade_id')->nullable()->index();  // loose grade-level ref
                $table->unsignedBigInteger('class_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_assignments');
        Schema::dropIfExists('skills');
    }
};
