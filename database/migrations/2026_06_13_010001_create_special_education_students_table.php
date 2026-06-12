<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_education_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->string('category', 40); // learning_disability|gifted|speech|physical|behavioral|visual|hearing|other
            $table->text('diagnosis')->nullable();
            $table->string('severity', 20)->nullable(); // mild|moderate|severe
            $table->unsignedBigInteger('assigned_specialist')->nullable();
            $table->string('status', 20)->default('active'); // active|inactive|graduated
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'student_id']);
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_specialist')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_education_students');
    }
};
