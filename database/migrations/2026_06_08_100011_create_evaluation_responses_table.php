<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_responses')) {
            return;
        }

        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('evaluation_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('indicator_id')->nullable()->index();
            $table->unsignedBigInteger('level_id')->nullable();   // chosen level (rubric / rating)
            $table->boolean('checklist_value')->nullable();       // checklist met / not met
            $table->decimal('score', 8, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_responses');
    }
};
