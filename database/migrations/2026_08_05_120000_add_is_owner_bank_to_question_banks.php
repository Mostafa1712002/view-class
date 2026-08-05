<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner («منصة الأول») tier flag. An owner bank is a super-admin bank held apart
 * from public banks: not freely copyable by schools, gated behind access
 * approval. Orthogonal to `visibility` (which stays the public/private driver).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->boolean('is_owner_bank')->default(false)->after('bank_type');
            $table->index('is_owner_bank');
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropIndex(['is_owner_bank']);
            $table->dropColumn('is_owner_bank');
        });
    }
};
