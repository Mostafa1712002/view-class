<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('job_title_id')->nullable()->after('specialization')
                ->constrained('job_titles')->nullOnDelete();
            $table->text('plain_password_for_card')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['job_title_id']);
            $table->dropColumn(['job_title_id', 'plain_password_for_card']);
        });
    }
};
