<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent CRM — school visits (Sprint 10, Trello #269). Additive; repo-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parent_school_visits')) {
            return;
        }

        Schema::create('parent_school_visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('parent_id')->index();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->date('visit_date');
            $table->time('visit_time')->nullable();
            $table->string('reason', 255);
            $table->unsignedBigInteger('met_staff_id')->nullable();
            $table->text('summary')->nullable();
            $table->text('next_action')->nullable();
            $table->date('followup_date')->nullable();
            $table->string('status', 30)->default('open');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_school_visits');
    }
};
