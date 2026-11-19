<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'bank_questions_external_idx';

    public function up(): void
    {
        if (! Schema::hasTable('bank_questions')
            || ! Schema::hasColumns('bank_questions', ['external_platform', 'external_id'])
            || Schema::hasIndex('bank_questions', self::INDEX)) {
            return;
        }
        Schema::table('bank_questions', fn (Blueprint $t) => $t->index(['external_platform', 'external_id'], self::INDEX));
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_questions') && Schema::hasIndex('bank_questions', self::INDEX)) {
            Schema::table('bank_questions', fn (Blueprint $t) => $t->dropIndex(self::INDEX));
        }
    }
};
