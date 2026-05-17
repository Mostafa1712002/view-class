<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('library_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('library_id')->nullable()->index(); // null => belongs to "public" pool
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            // video|pdf|image|presentation|link|other
            $table->string('content_type', 32)->default('other')->index();
            $table->string('file_path')->nullable();
            $table->string('external_url', 1024)->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->unsignedBigInteger('teacher_id')->nullable()->index();
            $table->string('tags')->nullable(); // comma-separated, simple search
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true)->index(); // mirrors library->type for fast filtering
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_items');
    }
};
