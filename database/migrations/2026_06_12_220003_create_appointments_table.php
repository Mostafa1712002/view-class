<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointments')) {
            return;
        }

        Schema::create('appointments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('schedule_id')->nullable()->index();
            $table->unsignedBigInteger('student_id')->nullable()->index(); // the student the visit concerns
            $table->unsignedBigInteger('booked_by')->nullable();           // student or parent who booked
            $table->unsignedBigInteger('bookable_role_id')->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable();      // the staff person
            $table->unsignedBigInteger('subject_id')->nullable();          // for معلم مادة
            $table->text('reason');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('contact_method', 20);                          // in_person|call|virtual
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status', 20)->default('requested');            // requested|confirmed|rejected|cancelled|completed
            $table->unsignedBigInteger('decision_by')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
