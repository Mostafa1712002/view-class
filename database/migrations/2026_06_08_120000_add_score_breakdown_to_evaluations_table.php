<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 8 Phase 3 — persist the scoring engine's full per-item breakdown so
 * "how was this result computed" stays reproducible after the form/snapshot
 * is edited (acceptance rule). Aggregates (total/max/percentage/grade_label)
 * already live on the evaluations table; this adds the structured detail.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->json('score_breakdown')->nullable()->after('grade_label');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn('score_breakdown');
        });
    }
};
