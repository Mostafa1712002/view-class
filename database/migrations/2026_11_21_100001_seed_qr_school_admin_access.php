<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QR attendance read pages (scanner, log, link, devices, print) moved from
 * granular qr.* slugs to qr.view; writes keep their granular slugs.
 *
 * - school-admin role: granted qr.view + the writes those pages use, but only
 *   when the role holds no qr.* row at all. A role already holding some qr.*
 *   rows was configured from the roles screen, so it is left as is (an
 *   explicit deny is never overridden).
 * - job titles: a title that held any old qr.* slug gets qr.view, so a page it
 *   could open before (e.g. scanner via qr.scan) still opens. Titles without
 *   any qr.* row are untouched.
 */
return new class extends Migration
{
    private const ROLE_SLUGS = ['qr.view', 'qr.scan', 'qr.close_day', 'qr.link_students', 'qr.link_devices'];

    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(['slug' => 'qr.view'],
            ['name' => 'عرض خدمات QR', 'group' => 'qr', 'created_at' => $now, 'updated_at' => $now]);
        $qrIds = DB::table('permissions')->where('slug', 'like', 'qr.%')->pluck('id');
        $viewId = DB::table('permissions')->where('slug', 'qr.view')->value('id');

        $roleId = DB::table('roles')->where('slug', 'school-admin')->value('id');
        if ($roleId !== null && ! DB::table('permission_role')->where('role_id', $roleId)->whereIn('permission_id', $qrIds)->exists()) {
            foreach (DB::table('permissions')->whereIn('slug', self::ROLE_SLUGS)->pluck('id') as $permissionId) {
                DB::table('permission_role')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $titleIds = DB::table('job_title_permissions')->whereIn('permission_id', $qrIds)->distinct()->pluck('job_title_id');
        foreach ($titleIds as $jobTitleId) {
            DB::table('job_title_permissions')->updateOrInsert(
                ['job_title_id' => $jobTitleId, 'permission_id' => $viewId],
                ['scope' => 'all', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        // Grants may already be edited from the roles/job-title screens; leave them in place.
    }
};
