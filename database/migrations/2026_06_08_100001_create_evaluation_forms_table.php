<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_forms')) {
            return;
        }

        Schema::create('evaluation_forms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index(); // null = global/company form
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('type')->default('rating_scale');       // rubric | rating_scale | checklist
            $table->string('usage_domain')->default('teacher');    // teacher|admin|class_visit|student|parent|school_environment|general|job_performance
            $table->string('status')->default('draft')->index();   // draft|ready|published|closed|archived
            $table->unsignedSmallInteger('levels_count')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('close_date')->nullable();
            $table->boolean('is_class_visit_only')->default(false)->index();
            $table->boolean('links_to_job_performance')->default(false)->index();
            $table->json('settings')->nullable();          // all boolean toggles (allow_edit, hide_percentages, …)
            $table->json('job_perf_settings')->nullable(); // linked item, weight, count-on, last vs average, party
            $table->dateTime('published_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_forms');
    }
};
