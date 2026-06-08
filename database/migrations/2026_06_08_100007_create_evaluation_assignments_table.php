<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_assignments')) {
            return;
        }

        Schema::create('evaluation_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('form_id')->index();
            $table->unsignedBigInteger('evaluator_id')->index();   // a user who evaluates
            $table->string('status')->default('assigned');         // assigned|in_progress|completed
            $table->dateTime('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'evaluator_id'], 'eval_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_assignments');
    }
};
