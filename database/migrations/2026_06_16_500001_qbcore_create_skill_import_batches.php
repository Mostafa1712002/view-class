<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #248 — skills Excel import staging. Mirrors question_import_batches but for the
 * skills taxonomy (which has no bank). preview_data holds the parsed+validated rows
 * across the preview → confirm step so we never stash large arrays in the session.
 * Additive only; nothing else is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('skill_import_batches')) {
            return;
        }

        Schema::create('skill_import_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->string('status', 20)->default('previewed');
            $table->json('preview_data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_import_batches');
    }
};
