<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grade_report_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_report_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['numeric', 'calculated', 'calculated_horizontal'])->default('numeric');
            $table->decimal('weight', 6, 2)->default(0);
            $table->decimal('max_score', 6, 2)->default(100);
            $table->text('formula')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_in_total')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['grade_report_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_report_columns');
    }
};
