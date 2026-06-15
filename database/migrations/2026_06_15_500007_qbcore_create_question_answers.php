<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#258): normalized question_answers table. Created ALONGSIDE
 * the legacy bank_questions.answer_data JSON — the JSON column is untouched and not
 * migrated here (a later screen card decides the cutover). question_id → bank_questions.
 * Supports text/image answers, fill-blank numbering, and matching columns (A/B).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_answers')) {
            return;
        }

        Schema::create('question_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->index(); // → bank_questions.id
            $table->text('answer_text')->nullable();
            $table->string('answer_image')->nullable();
            $table->enum('answer_content_type', ['text', 'image', 'text_and_image'])->default('text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('blank_number')->nullable();   // fill-blank
            $table->text('column_a_text')->nullable();                  // matching
            $table->string('column_a_image')->nullable();
            $table->text('column_b_text')->nullable();
            $table->string('column_b_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_answers');
    }
};
