<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QB rebuild foundation (#248): seed permission keys for the NEW taxonomy modules the
 * rebuild needs (compounds / skills / weeks / standards). Idempotent (updateOrInsert
 * by slug). The existing question_banks / exams / subjects groups are already seeded
 * by 2026_06_14_300001_seed_module_permissions and are intentionally untouched here.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $modules = [
            'compounds' => [
                'view'   => 'عرض المجمعات',
                'create' => 'إضافة مجمع',
                'edit'   => 'تعديل مجمع',
                'delete' => 'حذف مجمع',
            ],
            'skills' => [
                'view'   => 'عرض المهارات',
                'create' => 'إضافة مهارة',
                'edit'   => 'تعديل مهارة',
                'delete' => 'حذف مهارة',
                'import' => 'استيراد المهارات',
                'export' => 'تصدير المهارات',
            ],
            'weeks' => [
                'view'   => 'عرض الأسابيع الدراسية',
                'create' => 'إضافة أسبوع',
                'edit'   => 'تعديل أسبوع',
                'delete' => 'حذف أسبوع',
            ],
            'standards' => [
                'view'   => 'عرض المعايير',
                'create' => 'إضافة معيار',
                'edit'   => 'تعديل معيار',
                'delete' => 'حذف معيار',
            ],
        ];

        foreach ($modules as $group => $actions) {
            foreach ($actions as $action => $name) {
                DB::table('permissions')->updateOrInsert(
                    ['slug' => "{$group}.{$action}"],
                    ['name' => $name, 'group' => $group, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        foreach (['compounds', 'skills', 'weeks', 'standards'] as $group) {
            $ids = DB::table('permissions')->where('group', $group)->pluck('id');
            if ($ids->isEmpty()) {
                continue;
            }
            $inUse = DB::table('permission_role')->whereIn('permission_id', $ids)->count();
            if (DB::getSchemaBuilder()->hasTable('job_title_permissions')) {
                $inUse += DB::table('job_title_permissions')->whereIn('permission_id', $ids)->count();
            }
            if ($inUse === 0) {
                DB::table('permissions')->where('group', $group)->delete();
            }
        }
    }
};
