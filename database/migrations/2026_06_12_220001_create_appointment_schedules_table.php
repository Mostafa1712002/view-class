<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointment_schedules')) {
            return;
        }

        Schema::create('appointment_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('owner_id')->nullable();          // staff member offering slots
            $table->string('title');
            $table->date('date_from');
            $table->date('date_to');
            $table->json('days');                                         // [sun,mon,tue,…]
            $table->time('time_from');
            $table->time('time_to');
            $table->unsignedSmallInteger('slot_minutes')->default(30);
            $table->unsignedSmallInteger('max_appointments')->nullable();
            $table->string('location')->nullable();
            $table->string('mode', 20)->default('in_person');            // in_person|call|virtual
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');             // active|inactive|expired
            $table->tinyInteger('booking_open')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'owner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_schedules');
    }
};
