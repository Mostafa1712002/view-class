<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A school's request to copy from an owner («منصة الأول») bank. An `approved`
 * row is the grant the copy action checks. Public-bank copying needs no request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained('question_banks')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['question_bank_id', 'school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_access_requests');
    }
};
