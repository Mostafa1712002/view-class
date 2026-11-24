<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A school can have several agents (وكلاء); each one is flagged whether
        // their name prints on certificates by default.
        Schema::create('school_agents', function (Blueprint $table) {
            // ponytail: no FK, same as schools.agent_user_id (users.id type differs between installs).
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('user_id')->index();
            $table->boolean('print_on_certificate')->default(true);
            $table->timestamps();
            $table->primary(['school_id', 'user_id']);
        });

        DB::table('schools')->whereNotNull('agent_user_id')->orderBy('id')
            ->each(fn ($s) => DB::table('school_agents')->insertOrIgnore([
                'school_id' => $s->id, 'user_id' => $s->agent_user_id, 'print_on_certificate' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]));

        Schema::table('certificates', function (Blueprint $table) {
            // Agents chosen at issue time; null = the school's default (flagged) agents.
            $table->json('agent_user_ids')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', fn (Blueprint $t) => $t->dropColumn('agent_user_ids'));
        Schema::dropIfExists('school_agents');
    }
};
