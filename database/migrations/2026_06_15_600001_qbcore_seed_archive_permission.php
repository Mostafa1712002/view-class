<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QB rebuild CORE (#253): add the `question_banks.archive` permission key for the
 * new question-list archive action (distinct from delete). Idempotent. The other
 * question_banks keys were seeded earlier and stay untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'question_banks.archive'],
            ['name' => 'أرشفة سؤال', 'group' => 'question_banks', 'created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('slug', 'question_banks.archive')->value('id');
        if ($id === null) {
            return;
        }

        $inUse = DB::table('permission_role')->where('permission_id', $id)->count();
        if (DB::getSchemaBuilder()->hasTable('job_title_permissions')) {
            $inUse += DB::table('job_title_permissions')->where('permission_id', $id)->count();
        }
        if ($inUse === 0) {
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
