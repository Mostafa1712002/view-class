<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedBigInteger('teacher_id')->nullable()->index();
            $table->string('type', 16);                 // video|attachment|link
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('url', 512)->nullable();      // video embed / external link
            $table->string('file_path', 512)->nullable(); // private disk path for attachments
            $table->boolean('is_published')->default(false)->index();
            $table->date('available_from')->nullable();
            $table->date('available_until')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_contents');
    }
};
