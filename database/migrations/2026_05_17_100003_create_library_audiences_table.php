<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('library_audiences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('library_id')->index();
            // school|grade|class|user|teacher
            $table->string('audience_type', 32);
            $table->unsignedBigInteger('audience_id');
            $table->timestamps();

            $table->index(['library_id', 'audience_type']);
            $table->index(['audience_type', 'audience_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_audiences');
    }
};
