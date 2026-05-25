<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * === Anti-cheat card (ac) ===
 * Single active session token per in-progress attempt: each time the take page
 * is rendered the token rotates, so an older tab / device polling with a stale
 * token is locked out (newest opener wins).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_exams', function (Blueprint $table) {
            $table->string('session_token', 64)->nullable()->after('status');
            $table->unsignedInteger('exit_attempts_count')->default(0)->after('session_token');
            $table->boolean('auto_ended')->default(false)->after('exit_attempts_count');
        });
    }

    public function down(): void
    {
        Schema::table('student_exams', function (Blueprint $table) {
            $table->dropColumn(['session_token', 'exit_attempts_count', 'auto_ended']);
        });
    }
};
