<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('response_id')->index();
            $table->unsignedBigInteger('question_id')->index();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('response_id')->references('id')->on('survey_responses')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('survey_questions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
