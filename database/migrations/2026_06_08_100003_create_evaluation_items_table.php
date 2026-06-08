<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_items')) {
            return;
        }

        Schema::create('evaluation_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('form_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('weight', 6, 2)->default(0);    // relative weight (%)
            $table->decimal('max_score', 8, 2)->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('needs_evidence')->default(false);
            $table->boolean('evidence_required')->default(false);
            $table->boolean('allow_note')->default(true);
            $table->boolean('visible_to_evaluator_only')->default(false);
            $table->boolean('visible_to_subject_after_result')->default(true);
            $table->string('status')->default('active');    // active | disabled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_items');
    }
};
