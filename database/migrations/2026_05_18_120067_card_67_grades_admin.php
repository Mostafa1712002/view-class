<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add `is_active` + `subject_id` to grade_reports for richer filtering
        Schema::table('grade_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('grade_reports', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('type');
            }
            if (!Schema::hasColumn('grade_reports', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('grade_reports', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->after('class_id')->constrained('subjects')->nullOnDelete();
            }
        });

        // Per-column subject + pass threshold for grade_report_columns
        Schema::table('grade_report_columns', function (Blueprint $table) {
            if (!Schema::hasColumn('grade_report_columns', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->after('grade_report_id')->constrained('subjects')->nullOnDelete();
            }
            if (!Schema::hasColumn('grade_report_columns', 'pass_threshold')) {
                $table->decimal('pass_threshold', 6, 2)->nullable()->after('max_score');
            }
        });

        // Per-student per-column score storage for the dynamic flow
        if (!Schema::hasTable('student_grade_values')) {
            Schema::create('student_grade_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_report_id')->constrained()->cascadeOnDelete();
                $table->foreignId('grade_report_column_id')->constrained('grade_report_columns')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('score', 7, 2)->nullable();
                $table->text('note')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['grade_report_column_id', 'student_id'], 'sgv_unique_col_student');
                $table->index(['grade_report_id', 'student_id'], 'sgv_report_student_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grade_values');

        Schema::table('grade_report_columns', function (Blueprint $table) {
            if (Schema::hasColumn('grade_report_columns', 'subject_id')) {
                $table->dropForeign(['subject_id']);
                $table->dropColumn('subject_id');
            }
            if (Schema::hasColumn('grade_report_columns', 'pass_threshold')) {
                $table->dropColumn('pass_threshold');
            }
        });

        Schema::table('grade_reports', function (Blueprint $table) {
            if (Schema::hasColumn('grade_reports', 'subject_id')) {
                $table->dropForeign(['subject_id']);
                $table->dropColumn('subject_id');
            }
            if (Schema::hasColumn('grade_reports', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('grade_reports', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};
