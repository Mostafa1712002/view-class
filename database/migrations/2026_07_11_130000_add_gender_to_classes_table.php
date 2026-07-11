<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gender now belongs on the class (فصل), not the grade (صف) — card #315.
 * boys / girls / mixed (بنين / بنات / مختلط).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->enum('gender', ['boys', 'girls', 'mixed'])->nullable()->after('grade_level');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
