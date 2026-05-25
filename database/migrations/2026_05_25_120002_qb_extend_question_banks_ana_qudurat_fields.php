<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Question bank — extra future-link fields for the "Ana w al-Qudurat" platform.
 *
 * The schema is prepared up-front (card requirement §9) so the bank can later be
 * imported/exported/synced with the external platform without a rebuild. Nothing
 * here is wired into live behaviour yet — purely additive columns.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            if (! Schema::hasColumn('question_banks', 'external_platform')) {
                $table->string('external_platform', 100)->nullable()->after('external_id');
            }
            if (! Schema::hasColumn('question_banks', 'sync_status')) {
                $table->string('sync_status', 16)->nullable()->after('link_status');
            }
            if (! Schema::hasColumn('question_banks', 'imported_by')) {
                $table->foreignId('imported_by')->nullable()->after('created_by')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('question_banks', 'exportable')) {
                $table->boolean('exportable')->default(true)->after('is_ana_qudurat_linkable');
            }
            if (! Schema::hasColumn('question_banks', 'metadata')) {
                $table->json('metadata')->nullable()->after('exportable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            if (Schema::hasColumn('question_banks', 'imported_by')) {
                $table->dropForeign(['imported_by']);
                $table->dropColumn('imported_by');
            }
            foreach (['metadata', 'exportable', 'sync_status', 'external_platform'] as $col) {
                if (Schema::hasColumn('question_banks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
