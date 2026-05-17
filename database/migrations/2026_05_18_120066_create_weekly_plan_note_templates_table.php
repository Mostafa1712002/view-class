<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Card 66 — الخطة الأسبوعية / الملاحظات الجاهزة.
     *
     * Stores reusable note templates that admins/teachers can pick from while
     * authoring or reviewing weekly plans. One row per template, scoped to
     * a school (null = global template usable by every school).
     */
    public function up(): void
    {
        if (Schema::hasTable('weekly_plan_note_templates')) {
            return;
        }

        Schema::create('weekly_plan_note_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('title', 200)->nullable();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_plan_note_templates');
    }
};
