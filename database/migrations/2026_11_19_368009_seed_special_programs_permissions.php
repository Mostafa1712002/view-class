<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ACTIONS = [
        'view' => 'عرض البرامج التعليمية الخاصة',
        'manage' => 'إدارة تسجيل الطلاب والمجموعات والمعلمين',
        'exams' => 'إدارة اختبارات البرامج الخاصة',
        'questions' => 'إدارة بنك أسئلة البرامج الخاصة',
        'reports' => 'تقارير البرامج الخاصة',
        'settings' => 'إعدادات البرامج الخاصة',
    ];

    private const ROLE_GRANTS = [
        'school-admin' => ['view', 'manage', 'exams', 'reports', 'settings'],
        'teacher' => ['view', 'exams', 'reports'],
    ];

    private const JOB_TITLES = ['supervisor', 'vice', 'school_manager'];
    private const JOB_TITLE_ACTIONS = ['view', 'manage', 'exams', 'reports'];

    public function up(): void
    {
        $now = now();
        $ids = [];
        foreach (self::ACTIONS as $action => $name) {
            DB::table('permissions')->updateOrInsert(['slug' => "special_programs.$action"],
                ['name' => $name, 'group' => 'special_programs', 'created_at' => $now, 'updated_at' => $now]);
            $ids[$action] = DB::table('permissions')->where('slug', "special_programs.$action")->value('id');
        }

        foreach (self::ROLE_GRANTS as $roleSlug => $actions) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if ($roleId === null) {
                continue;
            }
            foreach ($actions as $action) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $ids[$action]],
                    ['created_at' => $now, 'updated_at' => $now]);
            }
        }

        foreach (DB::table('job_titles')->whereIn('slug', self::JOB_TITLES)->pluck('id') as $jobTitleId) {
            foreach (self::JOB_TITLE_ACTIONS as $action) {
                DB::table('job_title_permissions')->updateOrInsert(
                    ['job_title_id' => $jobTitleId, 'permission_id' => $ids[$action]],
                    ['scope' => 'school', 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('group', 'special_programs')->pluck('id');
        DB::table('job_title_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
