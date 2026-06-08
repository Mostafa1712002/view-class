<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluations')) {
            return;
        }

        Schema::create('evaluations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('form_id')->index();
            $table->unsignedBigInteger('snapshot_id')->nullable()->index(); // frozen structure used
            $table->unsignedBigInteger('evaluator_id')->index();
            $table->string('subject_type')->default('user'); // poly subject (usually User)
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('class_visit_id')->nullable()->index();
            $table->string('status')->default('draft')->index(); // draft|completed|pending_approval|approved|rejected|needs_review|locked
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade_label')->nullable();
            $table->unsignedInteger('items_completed')->default(0);
            $table->unsignedInteger('indicators_completed')->default(0);
            $table->unsignedInteger('evidence_count')->default(0);
            $table->text('general_notes')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
