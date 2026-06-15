<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * === Anti-cheat card (ac) — Trello #229 ===
 * Per-exam configurable limit for tab-exit / focus-loss events before the
 * attempt auto-locks. Default 3 preserves the previously hard-coded behaviour
 * (StudentExamController::AUTO_END_THRESHOLD).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('max_exit_attempts')->default(3)->after('attempts_allowed');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('max_exit_attempts');
        });
    }
};
