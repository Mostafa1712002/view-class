<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counseling_cases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('counselor_id')->index();
            $table->enum('category', ['academic', 'behavioral', 'social', 'health', 'psychological', 'other']);
            $table->string('title');
            $table->text('summary');
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('counseling_case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('counseling_cases')->cascadeOnDelete();
            $table->unsignedBigInteger('author_id')->index();
            $table->text('body');
            $table->string('action_taken')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_case_notes');
        Schema::dropIfExists('counseling_cases');
    }
};
