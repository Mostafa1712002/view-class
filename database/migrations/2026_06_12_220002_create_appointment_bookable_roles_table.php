<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointment_bookable_roles')) {
            return;
        }

        Schema::create('appointment_bookable_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('label');
            $table->string('target_type', 20);      // role|job_title|user|subject_teacher
            $table->unsignedBigInteger('target_id')->nullable(); // role_id / job_title_id / user_id
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_bookable_roles');
    }
};
