<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card 54 — Teachers page (المعلمين).
 * Adds a sibling profile table for the extra fields the Trello card requests
 * (granular name parts AR/EN, passport, birthplace, nationality, profile photo)
 * without polluting `users.$fillable` which is shared with other user roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Arabic name parts
            $table->string('first_name_ar', 80)->nullable();
            $table->string('father_name_ar', 80)->nullable();
            $table->string('grandfather_name_ar', 80)->nullable();
            $table->string('family_name_ar', 80)->nullable();

            // English name parts
            $table->string('first_name_en', 80)->nullable();
            $table->string('father_name_en', 80)->nullable();
            $table->string('grandfather_name_en', 80)->nullable();
            $table->string('family_name_en', 80)->nullable();

            // Identity & work
            $table->string('passport_number', 32)->nullable();

            // Personal
            $table->string('birth_place', 120)->nullable();
            $table->string('nationality', 80)->nullable();

            // Contact
            $table->string('phone_secondary', 32)->nullable();

            // Asset
            $table->string('profile_photo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
