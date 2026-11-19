<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Class visits are now gated by class_visits.view (job-title matrix) instead of
 * "any school admin". A job title with a configured matrix would otherwise lose
 * access, so grant it to every job title that already has permission rows.
 * Titles with no rows (and users with no title) keep the default-allow for .view.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(['slug' => 'class_visits.view'],
            ['name' => 'عرض الزيارات الصفية', 'group' => 'class_visits', 'created_at' => now(), 'updated_at' => now()]);
        $permissionId = DB::table('permissions')->where('slug', 'class_visits.view')->value('id');

        foreach (DB::table('job_title_permissions')->distinct()->pluck('job_title_id') as $jobTitleId) {
            DB::table('job_title_permissions')->updateOrInsert(
                ['job_title_id' => $jobTitleId, 'permission_id' => $permissionId],
                ['scope' => 'all', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Grants may already be edited from the matrix; leave them in place.
    }
};
