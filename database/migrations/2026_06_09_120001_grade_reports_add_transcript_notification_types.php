<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL ENUM requires explicit ALTER to add new values
        DB::statement("ALTER TABLE grade_reports MODIFY COLUMN `type` ENUM('dynamic','static','gradesheet','transcript','notification') NOT NULL DEFAULT 'dynamic'");
    }

    public function down(): void
    {
        // Revert to original enum values (rows with new types would become invalid — safe on rollback only if none exist)
        DB::statement("ALTER TABLE grade_reports MODIFY COLUMN `type` ENUM('dynamic','static','gradesheet') NOT NULL DEFAULT 'dynamic'");
    }
};
