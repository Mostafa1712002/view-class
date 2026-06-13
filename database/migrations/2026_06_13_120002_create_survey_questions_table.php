<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id')->index();
            $table->text('text');
            $table->string('type', 16)->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('survey_id')->references('id')->on('surveys')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
