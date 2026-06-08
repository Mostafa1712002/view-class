<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_levels')) {
            return;
        }

        Schema::create('evaluation_levels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('form_id')->index();
            $table->string('label');                               // text or numeric label
            $table->decimal('value', 8, 2)->default(0);            // numeric rank (1..N)
            $table->decimal('percentage', 5, 2)->nullable();       // rubric auto (N/levels)*100
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_levels');
    }
};
