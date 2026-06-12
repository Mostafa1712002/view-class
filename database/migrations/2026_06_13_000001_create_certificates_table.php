<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('type', 32);
            $table->string('title', 255);
            $table->unsignedBigInteger('recipient_user_id')->index();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->date('issue_date');
            $table->string('status', 16)->default('draft');
            $table->text('note')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('recipient_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
