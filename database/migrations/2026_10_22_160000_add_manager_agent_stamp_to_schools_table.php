<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // ponytail: no FK constraint — users.id type differs between installs; users are soft-deleted anyway.
            $table->unsignedBigInteger('manager_user_id')->nullable()->index()->after('student_gender');
            $table->unsignedBigInteger('agent_user_id')->nullable()->index()->after('manager_user_id');
            $table->string('stamp')->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['manager_user_id', 'agent_user_id', 'stamp']);
        });
    }
};
