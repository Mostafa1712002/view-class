<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Security fix: the earlier QR migrations (100001/100002) gave the school-admin ROLE write permissions
 * (qr.scan, qr.close_day, qr.link_students, qr.link_devices), bypassing job-title control.
 *
 * - GET pages get their own read slugs (qr.view_scanner, qr.view_log, qr.view_link, qr.view_devices, qr.print_card).
 * - school-admin role: gets the read slugs (if missing); loses the four write slugs. No operator had configured
 *   QR for that role before 2026-11-19, so every such grant came from those two migrations.
 * - job titles holding an old write slug get the matching read slug so their page still opens.
 */
return new class extends Migration
{
    private const READ = ['qr.view_scanner' => 'عرض صفحة الماسح', 'qr.view_log' => 'عرض السجل', 'qr.view_link' => 'عرض صفحة الربط',
        'qr.view_devices' => 'عرض الأجهزة', 'qr.print_card' => 'طباعة بطاقة'];

    private const WRITE_TO_READ = ['qr.scan' => 'qr.view_scanner', 'qr.link_students' => 'qr.view_link', 'qr.link_devices' => 'qr.view_devices'];

    private const REVOKE = ['qr.scan', 'qr.close_day', 'qr.link_students', 'qr.link_devices'];

    public function up(): void
    {
        $now = now();
        foreach (self::READ as $slug => $name) {
            if (! DB::table('permissions')->where('slug', $slug)->exists()) {
                DB::table('permissions')->insert(['slug' => $slug, 'name' => $name, 'group' => 'qr', 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $ids = DB::table('permissions')->where('slug', 'like', 'qr.%')->pluck('id', 'slug');

        $roleId = DB::table('roles')->where('slug', 'school-admin')->value('id');
        if ($roleId !== null) {
            foreach (array_keys(self::READ) as $slug) {
                if (! DB::table('permission_role')->where('role_id', $roleId)->where('permission_id', $ids[$slug])->exists()) {
                    DB::table('permission_role')->insert(['role_id' => $roleId, 'permission_id' => $ids[$slug], 'created_at' => $now, 'updated_at' => $now]);
                }
            }
            DB::table('permission_role')->where('role_id', $roleId)
                ->whereIn('permission_id', collect(self::REVOKE)->map(fn ($s) => $ids[$s] ?? null)->filter()->all())->delete();
        }

        foreach (self::WRITE_TO_READ as $write => $read) {
            if (! isset($ids[$write])) {
                continue;
            }
            foreach (DB::table('job_title_permissions')->where('permission_id', $ids[$write])->get() as $row) {
                DB::table('job_title_permissions')->updateOrInsert(
                    ['job_title_id' => $row->job_title_id, 'permission_id' => $ids[$read]],
                    ['scope' => $row->scope ?? 'all', 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Grants may have been edited from the roles/job-title screens since; leave them.
    }
};
