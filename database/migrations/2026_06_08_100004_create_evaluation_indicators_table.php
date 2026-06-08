<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_indicators')) {
            return;
        }

        Schema::create('evaluation_indicators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('form_id')->index();        // denormalised for fast form-wide queries
            $table->unsignedBigInteger('level_id')->nullable()->index(); // rubric: bound to a level
            $table->text('text');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('needs_note')->default(false);
            $table->boolean('needs_evidence')->default(false);
            $table->boolean('evidence_required')->default(false);
            $table->string('status')->default('active');           // active | disabled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_indicators');
    }
};
