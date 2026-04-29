<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grade_report_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_report_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('min_score', 6, 2);
            $table->decimal('max_score', 6, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('grade_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_report_ratings');
    }
};
