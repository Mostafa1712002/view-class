<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_student', function (Blueprint $table) {
            // Pass/fail per enrollment (student, class, year). Result lives on the
            // pivot because it is specific to a (student, class, year) — the year
            // is carried by classes.academic_year_id. See design.md decision #3.
            $table->enum('result', ['pending', 'passed', 'failed'])
                ->default('pending')
                ->after('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('class_student', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
