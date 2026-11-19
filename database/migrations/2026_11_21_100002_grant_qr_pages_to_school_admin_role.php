<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QR read pages are back on their own permissions (scanner=qr.scan, log=qr.view_log, link=qr.link_students,
 * devices=qr.link_devices, print=qr.print_card) so qr.view (default-allowed) never exposes device keys or cards.
 * School admins get those pages by default through their ROLE (user decision); job titles are not touched.
 */
return new class extends Migration
{
    private const SLUGS = ['qr.scan', 'qr.view_log', 'qr.link_students', 'qr.link_devices', 'qr.print_card'];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('slug', 'school-admin')->value('id');
        if ($roleId === null) {
            return;
        }
        $now = now();
        foreach (DB::table('permissions')->whereIn('slug', self::SLUGS)->pluck('id') as $permissionId) {
            if (! DB::table('permission_role')->where('role_id', $roleId)->where('permission_id', $permissionId)->exists()) {
                DB::table('permission_role')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Role grants may have been edited from the roles screen since; leave them.
    }
};
