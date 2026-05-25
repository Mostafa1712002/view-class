<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Question bank — widen the per-question status enum to the five client states.
 *
 * Card §8: draft / pending_review / approved / rejected / archived.
 * The legacy enum was draft|published|archived. We add the new states, keep the
 * old ones during transition, migrate `published` -> `approved`, then drop the
 * legacy `published` value.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('bank_questions', 'status')) {
            return;
        }

        // 1) Widen enum to the union of old + new states so the data migration is safe.
        DB::statement(
            "ALTER TABLE bank_questions MODIFY COLUMN status ENUM("
            . "'draft','published','pending_review','approved','rejected','archived'"
            . ") NOT NULL DEFAULT 'approved'"
        );

        // 2) Migrate legacy 'published' rows to the new 'approved' state.
        DB::table('bank_questions')->where('status', 'published')->update(['status' => 'approved']);

        // 3) Drop the legacy 'published' value now that no rows use it.
        DB::statement(
            "ALTER TABLE bank_questions MODIFY COLUMN status ENUM("
            . "'draft','pending_review','approved','rejected','archived'"
            . ") NOT NULL DEFAULT 'approved'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bank_questions', 'status')) {
            return;
        }

        DB::statement(
            "ALTER TABLE bank_questions MODIFY COLUMN status ENUM("
            . "'draft','published','pending_review','approved','rejected','archived'"
            . ") NOT NULL DEFAULT 'published'"
        );

        DB::table('bank_questions')->where('status', 'approved')->update(['status' => 'published']);

        DB::statement(
            "ALTER TABLE bank_questions MODIFY COLUMN status ENUM("
            . "'draft','published','archived'"
            . ") NOT NULL DEFAULT 'published'"
        );
    }
};
