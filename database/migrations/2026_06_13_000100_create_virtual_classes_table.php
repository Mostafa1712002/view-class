<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(45);
            $table->string('status', 20)->default('scheduled');
            $table->string('zoom_meeting_id', 40)->nullable();
            $table->text('join_url')->nullable();
            $table->text('start_url')->nullable();
            $table->string('passcode', 20)->nullable();
            $table->json('audience')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_classes');
    }
};
