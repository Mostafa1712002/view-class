<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QB rebuild STATES (#256): add the `question_banks.approve` + `question_banks.reject`
 * permission keys for the question review workflow. Both are non-".view" keys, so
 * canDo() fails closed for them (denied until explicitly granted). Idempotent. The
 * other question_banks keys were seeded earlier and stay untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $keys = [
            'question_banks.approve' => 'اعتماد سؤال',
            'question_banks.reject'  => 'رفض سؤال',
        ];

        foreach ($keys as $slug => $name) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'group' => 'question_banks', 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        foreach (['question_banks.approve', 'question_banks.reject'] as $slug) {
            $id = DB::table('permissions')->where('slug', $slug)->value('id');
            if ($id === null) {
                continue;
            }

            $inUse = DB::table('permission_role')->where('permission_id', $id)->count();
            if (DB::getSchemaBuilder()->hasTable('job_title_permissions')) {
                $inUse += DB::table('job_title_permissions')->where('permission_id', $id)->count();
            }
            if ($inUse === 0) {
                DB::table('permissions')->where('id', $id)->delete();
            }
        }
    }
};
