<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A — add advanced item-config columns to evaluation_items.
 * All columns are additive with safe defaults so existing rows keep scoring unchanged.
 *
 * @see .kiro/specs/trello-eval-v2/design.md — Phase A schema
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_items', function (Blueprint $table) {
            // Role responsible for filling this item (school-admin|supervisor|complex-manager|general-manager|auto|…)
            $table->string('responsible_role', 40)->nullable()->after('status');
            // manual|auto|evidence_only|mixed
            $table->string('item_type', 20)->default('manual')->after('responsible_role');
            // manual|auto_platform|after_evidence|external
            $table->string('calc_method', 20)->default('manual')->after('item_type');
            // Evidence must be approved before the item's percentage counts
            $table->tinyInteger('evidence_needs_approval')->default(0)->after('calc_method');
            // Whether the item value can be edited after it is sent for review
            $table->tinyInteger('editable_after_review')->default(0)->after('evidence_needs_approval');
            // Whether the item value can be edited after it is approved
            $table->tinyInteger('editable_after_approval')->default(0)->after('editable_after_review');
            // Minimum required percentage for this item (NULL = no minimum)
            $table->decimal('min_percentage', 5, 2)->nullable()->after('editable_after_approval');
            // Internal/admin notes not shown to the evaluator
            $table->text('internal_notes')->nullable()->after('min_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_items', function (Blueprint $table) {
            $table->dropColumn([
                'responsible_role',
                'item_type',
                'calc_method',
                'evidence_needs_approval',
                'editable_after_review',
                'editable_after_approval',
                'min_percentage',
                'internal_notes',
            ]);
        });
    }
};
