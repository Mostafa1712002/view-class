<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase E (#202) — Make evaluator_id nullable on evaluations.
 *
 * In shared_mode, ONE evaluation row per (form, subject) is created with no
 * single evaluator owner — evaluator_id is left NULL and individual contributions
 * are tracked via evaluation_responses.filled_by instead.
 *
 * Additive + safe: existing rows all have a non-null evaluator_id; this migration
 * only removes the NOT NULL constraint so shared-mode rows can be inserted.
 * No existing data is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->unsignedBigInteger('evaluator_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Only safe to re-add NOT NULL if no rows have NULL evaluator_id.
        // Down intentionally left as no-op to avoid data loss on shared-mode rows.
    }
};
