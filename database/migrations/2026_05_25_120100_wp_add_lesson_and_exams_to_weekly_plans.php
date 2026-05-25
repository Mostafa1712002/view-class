<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card 67 — تعديلات الخطة الأسبوعية.
 *
 * Adds the two per-plan content fields the client asked for that the schema
 * didn't already carry: عنوان الدرس (lesson_title) and الاختبارات (exams).
 * The existing objectives/topics/homework/notes columns cover the rest of
 * the requested item fields. Day / period / time are deferred to a future
 * weekly_plan_items child table (noted on the card).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('weekly_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('weekly_plans', 'lesson_title')) {
                $table->string('lesson_title', 255)->nullable()->after('topics');
            }
            if (!Schema::hasColumn('weekly_plans', 'exams')) {
                $table->text('exams')->nullable()->after('assessment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('weekly_plans', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['lesson_title', 'exams'],
                fn ($c) => Schema::hasColumn('weekly_plans', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
