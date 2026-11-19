<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sp_subjects', function (Blueprint $t) {
            $t->id();
            $t->string('program', 10);
            $t->string('code', 30)->unique();
            $t->string('name_ar');
            $t->string('name_en');
            $t->boolean('picks_passages')->default(false);
            $t->unsignedBigInteger('question_bank_id')->nullable()->index();
            $t->unsignedInteger('source_subject_id')->nullable();
            $t->unsignedTinyInteger('sort_order')->default(0);
            $t->index('program');
        });

        Schema::create('sp_skills', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('sp_subject_id')->index();
            $t->string('name');
            $t->enum('term', ['first', 'second', 'third'])->nullable();
            $t->unsignedInteger('source_skill_id')->nullable()->unique();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('sp_questions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bank_question_id')->unique();
            $t->unsignedBigInteger('sp_subject_id');
            $t->unsignedBigInteger('sp_skill_id')->nullable();
            $t->unsignedBigInteger('passage_id')->nullable()->index();
            $t->unsignedSmallInteger('sub_index')->default(0);
            $t->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $t->enum('term', ['first', 'second', 'both'])->nullable();
            $t->unsignedTinyInteger('week')->nullable();
            $t->unsignedInteger('question_order')->nullable();
            $t->boolean('has_image')->default(false);
            $t->char('content_hash', 32)->index();
            $t->json('merged_source_ids')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->index(['sp_subject_id', 'sp_skill_id', 'is_active'], 'sp_q_subject_skill_active');
            $t->index(['sp_subject_id', 'question_order'], 'sp_q_subject_order');
            $t->index(['sp_subject_id', 'difficulty'], 'sp_q_subject_difficulty');
        });

        Schema::create('sp_question_levels', function (Blueprint $t) {
            $t->unsignedBigInteger('sp_question_id');
            $t->unsignedTinyInteger('grade_level')->index();
            $t->primary(['sp_question_id', 'grade_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sp_question_levels');
        Schema::dropIfExists('sp_questions');
        Schema::dropIfExists('sp_skills');
        Schema::dropIfExists('sp_subjects');
    }
};
