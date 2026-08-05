<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classes are academic-year-scoped, so a grade legitimately reuses the same
 * class number across years (grade "الثاني الثانوي" has فصل 1 in every year).
 * The Phase 2 unique index (section_id, number) wrongly forbade that, which
 * blocked promotion (destination-year classes couldn't share source numbers).
 * Widen the uniqueness to (section_id, academic_year_id, number).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Add the wider index first: it leads with section_id so it can back
            // the section_id foreign key once the old index is dropped.
            $table->unique(['section_id', 'academic_year_id', 'number'], 'classes_section_year_number_unique');
            $table->dropUnique('classes_section_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_section_year_number_unique');
            $table->unique(['section_id', 'number'], 'classes_section_number_unique');
        });
    }
};
