<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Per-grade class number (رقم الفصل). Distinct from classes.grade_level
            // (the grade ordinal). Nullable until the guided backfill assigns it.
            $table->unsignedSmallInteger('number')->nullable()->after('division');

            // Rule #1: a class number is unique within its grade (section).
            $table->unique(['section_id', 'number'], 'classes_section_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_section_number_unique');
            $table->dropColumn('number');
        });
    }
};
