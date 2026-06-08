<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_assignment_targets')) {
            return;
        }

        Schema::create('evaluation_assignment_targets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('assignment_id')->index();
            $table->unsignedBigInteger('target_id')->index();      // -> evaluation_targets.id
            $table->timestamps();

            $table->unique(['assignment_id', 'target_id'], 'eval_assignment_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_assignment_targets');
    }
};
