<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grade_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->enum('type', ['dynamic', 'static', 'gradesheet'])->default('dynamic');
            $table->string('title');
            $table->date('grade_input_starts_at')->nullable();
            $table->date('grade_input_ends_at')->nullable();
            $table->date('calc_starts_at')->nullable();
            $table->date('calc_ends_at')->nullable();
            $table->date('opens_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->boolean('include_behavior')->default(false);
            $table->boolean('show_subject_bilingual')->default(false);
            $table->boolean('visible_to_student')->default(true);
            $table->boolean('visible_to_parent')->default(true);
            $table->boolean('visible_to_teacher')->default(true);
            $table->json('header_settings')->nullable();
            $table->json('footer_settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_reports');
    }
};
