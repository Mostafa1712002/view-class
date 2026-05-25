<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Question bank — multi-school sharing.
 *
 * A general (public) bank can be shared with a specific set of schools so the
 * platform can control exactly which schools see it. A public bank with NO rows
 * here stays platform-wide (visible to every school), preserving the previous
 * behaviour. Private banks ignore this pivot — they are scoped by school_id.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('question_bank_schools')) {
            return;
        }

        Schema::create('question_bank_schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained('question_banks')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['question_bank_id', 'school_id'], 'qb_school_unique');
            $table->index('school_id', 'qb_school_school_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_schools');
    }
};
