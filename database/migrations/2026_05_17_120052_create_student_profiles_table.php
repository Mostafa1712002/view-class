<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extended student profile fields requested by Trello card 52 (الطلاب).
 * Kept as a sibling table to avoid touching App\Models\User $fillable
 * while sibling agents work on parents/teachers/admins in parallel.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Arabic full-name breakdown
            $table->string('first_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('grandfather_name')->nullable();
            $table->string('last_name')->nullable();

            // English name breakdown
            $table->string('first_name_en')->nullable();
            $table->string('father_name_en')->nullable();
            $table->string('grandfather_name_en')->nullable();
            $table->string('last_name_en')->nullable();

            // Additional identification
            $table->string('fingerprint_id')->nullable();
            $table->string('seat_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('nationality')->nullable();
            $table->string('academic_id')->nullable();
            $table->string('birth_place')->nullable();
            $table->year('admission_year')->nullable();

            // Schooling history
            $table->string('previous_school')->nullable();
            $table->date('enrollment_date')->nullable();

            // Family
            $table->string('father_national_id')->nullable();
            $table->string('mother_national_id')->nullable();
            $table->string('mother_full_name')->nullable();

            // Contact
            $table->string('home_phone')->nullable();
            $table->text('address')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
