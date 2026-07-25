<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            // Standard grade ordinal 1..12 (see App\Modules\Promotion\Support\StandardGrades).
            // Nullable so legacy grades stay until the guided backfill assigns them.
            $table->unsignedTinyInteger('grade_number')->nullable()->after('level');

            // Gender-agnostic per card #C1: a grade is unique on (school, ordinal),
            // NOT per gender track. Per-gender promotion keys off classes.gender.
            // MySQL allows multiple NULLs under a unique index, so legacy rows are fine.
            $table->unique(['school_id', 'grade_number'], 'sections_school_grade_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique('sections_school_grade_unique');
            $table->dropColumn('grade_number');
        });
    }
};
