<?php

use App\Modules\Evaluation\Permissions\EvaluationPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase D (#208/#210) — seed the granular evaluation permission catalog and map
 * it to the admin roles via the `permission_role` pivot (the table
 * Role::hasPermission() reads). Idempotent (updateOrInsert) and reversible.
 *
 * Roles: 1 = super-admin (مدير النظام), 2 = school-admin (مدير المدرسة).
 * Both get ALL evaluation permissions so existing admin access is preserved —
 * super-admin also short-circuits in User::canEval().
 */
return new class extends Migration
{
    /** Roles that receive every evaluation permission. */
    private array $adminRoleIds = [1, 2];

    public function up(): void
    {
        $now = now();

        foreach (EvaluationPermissions::all() as $slug => $name) {
            // 1. Ensure the permission row exists (idempotent on slug).
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'group' => EvaluationPermissions::GROUP, 'updated_at' => $now]
            );
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            if (! $permissionId) {
                continue;
            }

            // 2. Map it to each admin role (idempotent on role_id+permission_id).
            foreach ($this->adminRoleIds as $roleId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->where('group', EvaluationPermissions::GROUP)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};
