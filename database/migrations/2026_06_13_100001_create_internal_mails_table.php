<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_mails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('sender_id')->index();
            $table->string('subject', 255);
            $table->string('importance', 16)->default('normal');
            $table->text('body');
            $table->string('attachment_path', 255)->nullable();
            $table->unsignedBigInteger('related_student_id')->nullable();
            $table->boolean('is_draft')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('related_student_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_mails');
    }
};
