<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase C (#205) — Educational-outcome records.
 *
 * Stores the result of computing a class/group average via one of two methods:
 *   all_registered  : absent students score 0
 *   attendees_only  : only present students counted in the denominator
 *
 * approval_status, final_average, method_used, and the count/sum columns are
 * deliberately NOT in the model's $fillable — they are written only by the
 * trusted ComputeEducationalOutcome and RecomputeEducationalOutcome actions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_outcomes', function (Blueprint $table) {
            $table->id();

            // Multi-tenant scope
            $table->unsignedInteger('school_id')->index();

            // Optional contextual references (nullable — manual entries may lack them)
            $table->unsignedInteger('educational_company_id')->nullable();
            $table->unsignedInteger('teacher_id')->nullable();
            $table->unsignedInteger('subject_id')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('class_label')->nullable();

            // Test metadata
            $table->string('test_name');
            $table->string('test_type')->nullable();

            // Origin of this record
            $table->string('source', 30)->default('manual');

            // Computed stats (set by the action, not mass-assigned)
            $table->unsignedInteger('registered_count')->default(0);
            $table->unsignedInteger('present_count')->default(0);
            $table->unsignedInteger('absent_count')->default(0);
            $table->decimal('scores_sum', 10, 2)->default(0);
            $table->string('method_used', 20);
            $table->decimal('final_average', 6, 2)->default(0);

            // Raw student data snapshot
            $table->json('students');

            // Temporal
            $table->date('test_date')->nullable();
            $table->timestamp('imported_at')->nullable();

            // Approval lifecycle
            $table->string('approval_status', 20)->default('draft');

            // Actor tracking
            $table->unsignedBigInteger('computed_by')->nullable();
            $table->timestamp('last_recomputed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_outcomes');
    }
};
