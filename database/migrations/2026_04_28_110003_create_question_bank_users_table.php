<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('question_bank_users', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['viewer', 'editor'])->default('viewer');
            $table->primary(['question_bank_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_users');
    }
};
