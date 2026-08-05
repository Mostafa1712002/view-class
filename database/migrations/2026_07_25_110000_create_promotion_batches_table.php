<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('source_year_id')->constrained('academic_years');
            $table->foreignId('destination_year_id')->constrained('academic_years');
            $table->enum('status', ['executed', 'rolled_back'])->default('executed');
            $table->json('summary')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users');
            $table->timestamp('executed_at')->nullable();
            $table->foreignId('rolled_back_by')->nullable()->constrained('users');
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_batches');
    }
};
