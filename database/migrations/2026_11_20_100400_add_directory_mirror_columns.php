<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Directory mirror (design §3.1, task 4.1): per-platform walk state, the platform's own active flag, the child lookup index. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hub_platforms', function (Blueprint $t) {
            $t->string('mirror_cursor')->nullable();          // next page to fetch; NULL with started_at = first page
            $t->timestamp('mirror_started_at')->nullable();   // set while a walk runs (also the run id)
            $t->boolean('mirror_full')->default(false);       // this walk ends with the missing sweep
            $t->timestamp('mirror_finished_at')->nullable();
        });

        Schema::table('hub_platform_accounts', function (Blueprint $t) {
            $t->boolean('platform_active')->nullable();       // `active` from GET /people (soft-deleted = false)
            $t->index(['platform_id', 'parent_external_id'], 'hub_acc_platform_parent_ext_idx');
        });
    }

    public function down(): void
    {
        Schema::table('hub_platform_accounts', function (Blueprint $t) {
            $t->dropIndex('hub_acc_platform_parent_ext_idx');
            $t->dropColumn('platform_active');
        });
        Schema::table('hub_platforms', function (Blueprint $t) {
            $t->dropColumn(['mirror_cursor', 'mirror_started_at', 'mirror_full', 'mirror_finished_at']);
        });
    }
};
