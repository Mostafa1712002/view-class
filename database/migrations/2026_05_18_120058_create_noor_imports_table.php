<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card 58 — نظام نور: persistent log of every Noor Excel import.
 * Plus a sibling table to hold the academic_number we don't want
 * to add to users (User.php is locked by coordination rules).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noor_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->comment('uploader')->index();
            $table->string('type', 32)->index();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('status', 16)->default('pending'); // pending|processing|completed|failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('errors')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('noor_user_academics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('academic_number', 64)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noor_user_academics');
        Schema::dropIfExists('noor_imports');
    }
};
