<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['mcq', 'true_false', 'short', 'essay'])->default('mcq');
            $table->text('body_ar');
            $table->text('body_en')->nullable();
            $table->json('answer_data')->nullable();
            $table->unsignedTinyInteger('difficulty')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['question_bank_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_questions');
    }
};
