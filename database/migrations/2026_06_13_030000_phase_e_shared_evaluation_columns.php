<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase E (#202, #203) — Shared evaluation mode + per-item state columns.
 *
 * Strictly additive with safe defaults so all legacy (shared_mode=0) rows and
 * existing code paths are completely unaffected. New logic is gated by
 * `if ($form->shared_mode)` in the application layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- evaluation_forms: opt-in flag for shared mode ---
        if (!Schema::hasColumn('evaluation_forms', 'shared_mode')) {
            Schema::table('evaluation_forms', function (Blueprint $table) {
                // Default 0 = legacy per-evaluator behaviour; 1 = shared single evaluation per subject
                $table->tinyInteger('shared_mode')->default(0)->after('status');
            });
        }

        // --- evaluation_responses: per-item state columns ---
        Schema::table('evaluation_responses', function (Blueprint $table) {
            // Which role was responsible for this response at fill time (denormalised from item)
            if (!Schema::hasColumn('evaluation_responses', 'responsible_role')) {
                $table->string('responsible_role', 40)->nullable()->after('note');
            }
            // The user who actually filled this response
            if (!Schema::hasColumn('evaluation_responses', 'filled_by')) {
                $table->unsignedBigInteger('filled_by')->nullable()->after('responsible_role');
            }
            // Per-item lifecycle: draft|completed|pending_review|approved|rejected
            // Default 'draft' keeps legacy responses inert to the new lock logic.
            if (!Schema::hasColumn('evaluation_responses', 'item_status')) {
                $table->string('item_status', 20)->default('draft')->after('filled_by');
            }
            // Timestamp when this item was submitted by its filler
            if (!Schema::hasColumn('evaluation_responses', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('item_status');
            }
            // Who approved this item response and when
            if (!Schema::hasColumn('evaluation_responses', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('evaluation_responses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            // Reason given when this item response was rejected (returns it to draft)
            if (!Schema::hasColumn('evaluation_responses', 'reject_reason')) {
                $table->text('reject_reason')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_responses', function (Blueprint $table) {
            $cols = ['responsible_role', 'filled_by', 'item_status', 'submitted_at', 'approved_by', 'approved_at', 'reject_reason'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('evaluation_responses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::hasColumn('evaluation_forms', 'shared_mode')) {
            Schema::table('evaluation_forms', function (Blueprint $table) {
                $table->dropColumn('shared_mode');
            });
        }
    }
};
