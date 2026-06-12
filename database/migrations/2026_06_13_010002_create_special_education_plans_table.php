<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_education_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('se_student_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title', 160);
            $table->text('goals')->nullable();
            $table->text('accommodations')->nullable();
            $table->date('start_date')->nullable();
            $table->date('review_date')->nullable();
            $table->string('status', 20)->default('active'); // draft|active|completed
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('se_student_id')->references('id')->on('special_education_students')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_education_plans');
    }
};
