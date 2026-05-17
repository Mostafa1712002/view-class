<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            if (! Schema::hasColumn('question_banks', 'description')) {
                $table->text('description')->nullable()->after('name_en');
            }
            if (! Schema::hasColumn('question_banks', 'visibility')) {
                // public => available across schools / platform-wide
                // private => scoped to school_id
                $table->string('visibility', 16)->default('private')->after('description');
            }
            if (! Schema::hasColumn('question_banks', 'status')) {
                // active | inactive | under_review | archived
                $table->string('status', 16)->default('active')->after('visibility');
            }
            if (! Schema::hasColumn('question_banks', 'source')) {
                // manual | library | import | ana_qudurat
                $table->string('source', 16)->default('manual')->after('status');
            }
            if (! Schema::hasColumn('question_banks', 'grade_level')) {
                $table->unsignedTinyInteger('grade_level')->nullable()->after('source');
            }
            if (! Schema::hasColumn('question_banks', 'category_type')) {
                // school | qudurat | verbal | quantitative | speed_reading
                $table->string('category_type', 32)->nullable()->after('grade_level');
            }
            if (! Schema::hasColumn('question_banks', 'is_ana_qudurat_linkable')) {
                $table->boolean('is_ana_qudurat_linkable')->default(false)->after('category_type');
            }
            if (! Schema::hasColumn('question_banks', 'external_id')) {
                $table->string('external_id', 100)->nullable()->after('is_ana_qudurat_linkable');
            }
            if (! Schema::hasColumn('question_banks', 'link_status')) {
                $table->string('link_status', 16)->nullable()->after('external_id');
            }
            if (! Schema::hasColumn('question_banks', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable()->after('link_status');
            }
        });

        // Best-effort indices (some MySQL versions choke on duplicate creates)
        try {
            Schema::table('question_banks', function (Blueprint $table) {
                $table->index(['visibility', 'status'], 'qb_visibility_status_idx');
            });
        } catch (\Throwable $e) {
            // ignore — index probably already exists
        }
        try {
            Schema::table('question_banks', function (Blueprint $table) {
                $table->index('source', 'qb_source_idx');
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            try { $table->dropIndex('qb_visibility_status_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('qb_source_idx'); } catch (\Throwable $e) {}

            foreach ([
                'last_sync_at', 'link_status', 'external_id', 'is_ana_qudurat_linkable',
                'category_type', 'grade_level', 'source', 'status', 'visibility', 'description',
            ] as $col) {
                if (Schema::hasColumn('question_banks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
