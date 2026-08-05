<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('promotion_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users');
            $table->enum('action', ['promoted', 'graduated', 'overflow_moved', 'not_moved']);
            $table->unsignedBigInteger('from_section_id')->nullable();
            $table->unsignedBigInteger('from_class_id')->nullable();
            $table->unsignedBigInteger('to_section_id')->nullable();
            $table->unsignedBigInteger('to_class_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_batch_items');
    }
};
