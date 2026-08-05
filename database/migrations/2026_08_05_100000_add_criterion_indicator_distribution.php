<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trello #336 — per-indicator percentage + responsible-user picker.
 *
 * - evaluation_indicators.weight: each indicator's share of its criterion's
 *   relative weight (Σ indicators ≤ criterion weight). NULL = unset.
 * - evaluation_items.responsible_user_id: the specific admin/supervisor account
 *   chosen for the criterion. responsible_role stays as-is (execution still
 *   distributes items by role slug) and is derived from the chosen user's role.
 *
 * Both additive with safe defaults; existing scoring unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_indicators') && !Schema::hasColumn('evaluation_indicators', 'weight')) {
            Schema::table('evaluation_indicators', function (Blueprint $table) {
                $table->decimal('weight', 6, 2)->nullable()->after('sort_order');
            });
        }

        if (Schema::hasTable('evaluation_items') && !Schema::hasColumn('evaluation_items', 'responsible_user_id')) {
            Schema::table('evaluation_items', function (Blueprint $table) {
                $table->unsignedBigInteger('responsible_user_id')->nullable()->after('responsible_role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('evaluation_indicators', 'weight')) {
            Schema::table('evaluation_indicators', function (Blueprint $table) {
                $table->dropColumn('weight');
            });
        }
        if (Schema::hasColumn('evaluation_items', 'responsible_user_id')) {
            Schema::table('evaluation_items', function (Blueprint $table) {
                $table->dropColumn('responsible_user_id');
            });
        }
    }
};
