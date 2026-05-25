<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Noor import (نظام نور) — card "نظام الاستيراد نور".
 *
 * Adds the columns needed for the two-step preview → execute flow and for
 * tracking parent (ولي الأمر) creation/linking counts on the import log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('noor_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('noor_imports', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('school_id')->index();
            }
            if (! Schema::hasColumn('noor_imports', 'preview_data')) {
                // Holds the parsed + classified rows so "تنفيذ الاستيراد"
                // does not need to re-parse the file.
                $table->longText('preview_data')->nullable()->after('errors');
            }
            if (! Schema::hasColumn('noor_imports', 'parent_created_count')) {
                $table->unsignedInteger('parent_created_count')->default(0)->after('failed_count');
            }
            if (! Schema::hasColumn('noor_imports', 'parent_updated_count')) {
                $table->unsignedInteger('parent_updated_count')->default(0)->after('parent_created_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('noor_imports', function (Blueprint $table) {
            foreach (['academic_year_id', 'preview_data', 'parent_created_count', 'parent_updated_count'] as $col) {
                if (Schema::hasColumn('noor_imports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
