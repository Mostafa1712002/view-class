<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Card #291 fix — teachers (and school-admins) hit 403 on
 * /manage/virtual-classes/create and /manage/discussion-rooms/create.
 *
 * Root cause: 2026_06_15_400000_seed_communications_permissions.php inserted the
 * permission ROWS but granted them to no role. canDo() default-allows only
 * ".view", so every create/edit/delete/start action fails closed → 403.
 *
 * This grants the virtual-classes + discussion management permissions to the
 * teacher and school-admin roles via permission_role (the table
 * Role::hasPermission() reads). Idempotent + reversible. Super-admin is
 * unaffected (canDo() short-circuits).
 */
return new class extends Migration
{
    private array $slugs = [
        'virtual_classes.view', 'virtual_classes.create', 'virtual_classes.edit',
        'virtual_classes.delete', 'virtual_classes.start', 'virtual_classes.join',
        'virtual_classes.view_attendance', 'virtual_classes.recalc_attendance',
        'discussion.view', 'discussion.create', 'discussion.edit',
        'discussion.delete', 'discussion.toggle_comments',
    ];

    private array $roleSlugs = ['teacher', 'school-admin'];

    public function up(): void
    {
        $now = now();
        $permIds = DB::table('permissions')->whereIn('slug', $this->slugs)->pluck('id');
        $roleIds = DB::table('roles')->whereIn('slug', $this->roleSlugs)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')->whereIn('slug', $this->slugs)->pluck('id');
        $roleIds = DB::table('roles')->whereIn('slug', $this->roleSlugs)->pluck('id');

        DB::table('permission_role')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permIds)
            ->delete();
    }
};
