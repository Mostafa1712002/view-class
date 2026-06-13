<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('source_bank_question_id')
                ->nullable()
                ->after('order')
                ->comment('FK to bank_questions; set when a question was copied from a bank');

            $table->index('source_bank_question_id', 'eq_source_bq_idx');

            $table->foreign('source_bank_question_id', 'eq_source_bq_fk')
                ->references('id')
                ->on('bank_questions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropForeign('eq_source_bq_fk');
            $table->dropIndex('eq_source_bq_idx');
            $table->dropColumn('source_bank_question_id');
        });
    }
};
