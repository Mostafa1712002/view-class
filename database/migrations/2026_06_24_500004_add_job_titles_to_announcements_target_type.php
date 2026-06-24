<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA bounce (card #232): widen announcements.target_type to allow the new
 * 'job_titles' audience (alongside the existing categories). Without this the
 * insert truncates the enum value and fails.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('announcements')) {
            return;
        }

        DB::statement(
            "ALTER TABLE announcements MODIFY COLUMN target_type "
            . "ENUM('all','students','teachers','parents','admins','specific_users','specific_roles','job_titles') "
            . "NOT NULL DEFAULT 'all'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('announcements')) {
            return;
        }

        DB::table('announcements')->where('target_type', 'job_titles')->update(['target_type' => 'all']);

        DB::statement(
            "ALTER TABLE announcements MODIFY COLUMN target_type "
            . "ENUM('all','students','teachers','parents','admins','specific_users','specific_roles') "
            . "NOT NULL DEFAULT 'all'"
        );
    }
};
