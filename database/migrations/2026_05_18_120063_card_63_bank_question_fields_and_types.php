<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Card 63 — question CRUD inside a bank.
 * Adds the columns needed for the richer question editor (points, lesson link,
 * attachment, status, classification) and widens the type enum to cover the
 * five client-listed kinds (true/false, MCQ, essay, matching, fill-blank).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bank_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_questions', 'lesson_id')) {
                $table->foreignId('lesson_id')->nullable()->after('question_bank_id')
                    ->constrained('subject_lessons')->nullOnDelete();
            }
            if (! Schema::hasColumn('bank_questions', 'points')) {
                $table->decimal('points', 6, 2)->default(1)->after('difficulty');
            }
            if (! Schema::hasColumn('bank_questions', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('points');
            }
            if (! Schema::hasColumn('bank_questions', 'status')) {
                $table->enum('status', ['draft', 'published', 'archived'])->default('published')->after('attachment_path');
            }
            if (! Schema::hasColumn('bank_questions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // Widen the type enum — Laravel's enum() change requires doctrine/dbal,
        // so use a raw ALTER for portability across the deployed MySQL.
        DB::statement(
            "ALTER TABLE bank_questions MODIFY COLUMN type ENUM("
            ."'mcq','true_false','short','essay','matching','fill_blank'"
            .") NOT NULL DEFAULT 'mcq'"
        );
    }

    public function down(): void
    {
        Schema::table('bank_questions', function (Blueprint $table) {
            if (Schema::hasColumn('bank_questions', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('bank_questions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('bank_questions', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
            if (Schema::hasColumn('bank_questions', 'points')) {
                $table->dropColumn('points');
            }
            if (Schema::hasColumn('bank_questions', 'lesson_id')) {
                $table->dropForeign(['lesson_id']);
                $table->dropColumn('lesson_id');
            }
        });

        DB::statement(
            "ALTER TABLE bank_questions MODIFY COLUMN type ENUM("
            ."'mcq','true_false','short','essay'"
            .") NOT NULL DEFAULT 'mcq'"
        );
    }
};
