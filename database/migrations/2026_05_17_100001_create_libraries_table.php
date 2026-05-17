<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('libraries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['public', 'private'])->default('public');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libraries');
    }
};
