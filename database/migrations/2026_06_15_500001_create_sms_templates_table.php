<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint-9 / Trello #238 — SMS message templates.
 *
 * Tenant-owned reusable templates with placeholder variables ({first_name},
 * {student_name}, …). Title is unique per school. Soft-deletes so a template
 * referenced by historic batches/messages can be retired without breaking
 * report joins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('body');
            // language classification of the body (drives the 70 vs 160 segment math)
            $table->enum('lang', ['ar', 'en', 'mixed'])->default('ar');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Title unique within a school (NULL-deleted rows excluded via soft delete handling in repo).
            $table->unique(['school_id', 'title']);
            $table->index(['school_id', 'is_active']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
