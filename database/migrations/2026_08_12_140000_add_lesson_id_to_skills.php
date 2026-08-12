<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('skills') && ! Schema::hasColumn('skills', 'lesson_id')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->foreignId('lesson_id')->nullable()->after('week_id')->constrained('subject_lessons')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('skills') && Schema::hasColumn('skills', 'lesson_id')) {
            Schema::table('skills', function (Blueprint $table) {
                try {
                    $table->dropConstrainedForeignId('lesson_id');
                } catch (\Throwable $e) {
                    $table->dropColumn('lesson_id');
                }
            });
        }
    }
};
