<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_targets')) {
            return;
        }

        Schema::create('evaluation_targets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('form_id')->index();
            $table->string('target_type')->default('user'); // poly: user|school|class|subject|section…
            $table->unsignedBigInteger('target_id')->index();
            $table->json('meta')->nullable();               // school_id, stage, subject for filtering
            $table->boolean('added_after_publish')->default(false);
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'target_type', 'target_id'], 'eval_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_targets');
    }
};
