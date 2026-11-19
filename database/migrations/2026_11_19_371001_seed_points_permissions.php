<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view' => 'عرض النقاط والمكافآت',
        'manage_rules' => 'إدارة قواعد النقاط',
        'adjust' => 'تعديل النقاط يدويا',
        'manage_rewards' => 'إدارة المكافآت',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')->where('slug', 'school-admin')->pluck('id');
        foreach (self::PERMISSIONS as $action => $name) {
            DB::table('permissions')->updateOrInsert(['slug' => "points.$action"],
                ['name' => $name, 'group' => 'points', 'created_at' => now(), 'updated_at' => now()]);
            $permissionId = DB::table('permissions')->where('slug', "points.$action")->value('id');
            foreach ($roleIds as $roleId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('group', 'points')->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('job_title_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
