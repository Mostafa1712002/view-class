<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compounds', function (Blueprint $table) {
            // ponytail: no FK, same as schools.manager_user_id.
            $table->unsignedBigInteger('manager_user_id')->nullable()->index();
        });
        Schema::table('certificates', function (Blueprint $table) {
            // null = the school's default agent; print_agent=false = print no agent.
            $table->unsignedBigInteger('agent_user_id')->nullable();
            $table->boolean('print_agent')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('compounds', fn (Blueprint $t) => $t->dropColumn('manager_user_id'));
        Schema::table('certificates', fn (Blueprint $t) => $t->dropColumn(['agent_user_id', 'print_agent']));
    }
};
