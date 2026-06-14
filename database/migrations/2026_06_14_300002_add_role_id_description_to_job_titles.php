<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('school_id')
                ->constrained()->nullOnDelete();
            $table->string('description', 500)->nullable()->after('name_en');
        });
    }

    public function down(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'description']);
        });
    }
};
