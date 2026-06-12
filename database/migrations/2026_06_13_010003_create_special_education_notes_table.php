<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_education_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('se_student_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->text('body');
            $table->date('note_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('se_student_id')->references('id')->on('special_education_students')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_education_notes');
    }
};
