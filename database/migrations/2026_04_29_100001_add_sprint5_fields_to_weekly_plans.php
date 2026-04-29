<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('weekly_plans', function (Blueprint $table) {
            $table->boolean('is_prepared')->default(false)->after('is_locked');
            $table->timestamp('prepared_at')->nullable()->after('is_prepared');
            $table->json('attachments')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_plans', function (Blueprint $table) {
            $table->dropColumn(['is_prepared', 'prepared_at', 'attachments']);
        });
    }
};
