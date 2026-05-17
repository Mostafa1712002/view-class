<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card 65 — Books module.
 * Admin uploads digital books (PDF) per subject + grade + term, students read via the
 * "Books" sidebar entry. Ministry books for the Ahli track are also stored here with
 * is_ministry=true so they can be added without re-uploading.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index(); // null => ministry/global pool
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedSmallInteger('grade_level')->nullable()->index();
            $table->unsignedBigInteger('academic_term_id')->nullable()->index();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('source', ['file', 'external_url'])->default('file')->index();
            $table->string('file_path')->nullable();          // storage/app/public/books/...
            $table->string('external_url', 1024)->nullable();
            $table->string('cover_path')->nullable();

            $table->boolean('is_ministry')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'subject_id', 'grade_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
