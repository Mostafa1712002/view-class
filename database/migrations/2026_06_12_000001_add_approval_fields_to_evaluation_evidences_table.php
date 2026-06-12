<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_evidences', function (Blueprint $table) {
            // Phase B (#204) — evidence approval workflow.
            // DEFAULT 'approved' is intentional: existing evidence keeps counting in scoring
            // without any manual review action. Only new evidence uploaded after this migration
            // can land in 'uploaded'/'pending_approval' states.
            $table->string('status', 20)->notNull()->default('approved')->after('description');
            $table->string('source', 20)->notNull()->default('manual')->after('status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('source');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');

            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_evidences', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['status', 'source', 'reviewed_by', 'reviewed_at', 'review_note']);
        });
    }
};
