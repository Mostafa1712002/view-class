<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_visits')) {
            return;
        }

        Schema::create('class_visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('supervisor_id')->index();   // evaluator
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->unsignedBigInteger('stage_id')->nullable();
            $table->unsignedBigInteger('class_room_id')->nullable()->index();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('period_id')->nullable();
            $table->unsignedBigInteger('form_id')->nullable()->index();
            $table->unsignedBigInteger('evaluation_id')->nullable()->index(); // set on execution (no hard FK — circular with evaluations)
            $table->string('visit_type')->default('announced');     // announced | secret | …
            $table->boolean('notify_teacher')->default(true);
            $table->text('pre_notes')->nullable();
            $table->date('visit_date')->nullable();
            $table->time('visit_time')->nullable();
            $table->string('status')->default('scheduled')->index(); // scheduled|secret|teacher_notified|in_progress|completed|postponed|cancelled|missed
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_visits');
    }
};
