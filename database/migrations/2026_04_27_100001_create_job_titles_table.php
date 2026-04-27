<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug', 64);
            $table->string('name_ar', 120);
            $table->string('name_en', 120);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'slug'], 'job_titles_school_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};
