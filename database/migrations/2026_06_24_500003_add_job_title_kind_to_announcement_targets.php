<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA bounce (card #232): the "الفئة المستهدفة" needs to target specific job
 * titles (مدير مدرسة، مشرف تعليمي، وكيل، …) — distinct from the 5 high-level
 * roles. We persist them through the existing announcement_targets table with a
 * new kind = 'job_title'. This widens the enum to add that value.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('announcement_targets')) {
            return;
        }

        DB::statement(
            "ALTER TABLE announcement_targets MODIFY COLUMN kind ENUM('user','role','job_title') NOT NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('announcement_targets')) {
            return;
        }

        // Drop any job_title rows first so the column can narrow back safely.
        DB::table('announcement_targets')->where('kind', 'job_title')->delete();

        DB::statement(
            "ALTER TABLE announcement_targets MODIFY COLUMN kind ENUM('user','role') NOT NULL"
        );
    }
};
