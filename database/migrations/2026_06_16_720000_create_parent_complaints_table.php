<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent CRM — complaints (Sprint 10, Trello #269).
 * Additive table; scope enforced in the repository, FK constraints omitted
 * (legacy convention) to avoid type/charset mismatch on migrate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parent_complaints')) {
            return;
        }

        Schema::create('parent_complaints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('parent_id')->index();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('code', 20)->index();
            $table->string('type', 40)->nullable();
            $table->date('complaint_date');
            $table->string('purpose', 255);
            $table->text('details')->nullable();
            $table->text('action_required')->nullable();
            $table->text('actions_taken')->nullable();
            $table->string('priority', 10)->default('normal');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('status', 30)->default('new');
            $table->string('attachment_path', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_complaints');
    }
};
