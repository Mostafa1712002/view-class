<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('behavior_records')) {
            return;
        }

        Schema::create('behavior_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->enum('scope', ['student', 'teacher'])->index();
            $table->unsignedBigInteger('subject_user_id')->index(); // the student/teacher the behaviour is recorded for
            $table->unsignedBigInteger('behavior_id')->index();
            $table->unsignedBigInteger('behavior_action_id')->nullable()->index();
            $table->integer('points')->default(0);                  // signed delta actually applied
            $table->text('note')->nullable();
            $table->boolean('needs_followup')->default(false);
            $table->boolean('notified_parent')->default(false);
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_records');
    }
};
