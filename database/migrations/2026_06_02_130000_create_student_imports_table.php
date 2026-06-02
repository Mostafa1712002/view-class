<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card #108 — استيراد الطلاب من ملف إكسل: persistent log/archive of every
 * Excel student import. Kept separate from `noor_imports` so the two
 * operation archives never cross-contaminate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->comment('uploader')->index();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('status', 16)->default('previewed'); // previewed|completed|failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('parent_created_count')->default(0);
            $table->json('preview_data')->nullable();
            $table->json('errors')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_imports');
    }
};
