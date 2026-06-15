<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The DB-level unique(['school_id','title']) counts soft-deleted rows, so the
 * cycle create→delete→create the same title throws a duplicate-key 500 even
 * though the app rule (which excludes trashed rows) passes. Drop the DB unique
 * and rely on the soft-delete-aware validation rule in SmsTemplateController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropUnique('sms_templates_school_id_title_unique');
            // keep a non-unique index for lookups
            $table->index(['school_id', 'title'], 'sms_templates_school_id_title_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropIndex('sms_templates_school_id_title_idx');
            $table->unique(['school_id', 'title']);
        });
    }
};
