<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed the "viewing.login_as_user" permission (الدخول كمستخدم للإطلاع).
 *
 * Gates ImpersonateController::start/confirm for non-super-admin users who
 * should be allowed to view-login. Super-admins always bypass this gate via
 * User::canDo() → isSuperAdmin() short-circuit.
 *
 * Roles: 1 = super-admin (مدير النظام) — receives it to keep permission_role
 * consistent; the guard short-circuits before the DB check for super-admins.
 *
 * Idempotent (updateOrInsert on slug).
 */
return new class extends Migration
{
    private const SLUG  = 'viewing.login_as_user';
    private const NAME  = 'الدخول كمستخدم للإطلاع';
    private const GROUP = 'viewing';

    /** Roles that receive this permission. */
    private array $adminRoleIds = [1]; // super-admin

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => self::SLUG],
            ['name' => self::NAME, 'group' => self::GROUP, 'updated_at' => $now]
        );

        $permissionId = DB::table('permissions')->where('slug', self::SLUG)->value('id');
        if (! $permissionId) {
            return;
        }

        foreach ($this->adminRoleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('slug', self::SLUG)->value('id');
        if ($id) {
            DB::table('permission_role')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
