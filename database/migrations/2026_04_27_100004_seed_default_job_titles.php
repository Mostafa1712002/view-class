<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $titles = [
            ['slug' => 'school_manager',  'name_ar' => 'مدير مدرسة',         'name_en' => 'School Manager',    'sort_order' => 1],
            ['slug' => 'system_assistant','name_ar' => 'مساعد مدير نظام',    'name_en' => 'System Assistant',  'sort_order' => 2],
            ['slug' => 'supervisor',      'name_ar' => 'مشرف تعليمي',        'name_en' => 'Educational Supervisor','sort_order' => 3],
            ['slug' => 'vice',            'name_ar' => 'وكيل',               'name_en' => 'Vice Principal',    'sort_order' => 4],
            ['slug' => 'activity_lead',   'name_ar' => 'رائد نشاط',          'name_en' => 'Activity Lead',     'sort_order' => 5],
            ['slug' => 'clinic',          'name_ar' => 'مسؤول عيادة',        'name_en' => 'Clinic Officer',    'sort_order' => 6],
            ['slug' => 'canteen',         'name_ar' => 'مسؤول مقصف',         'name_en' => 'Canteen Officer',   'sort_order' => 7],
            ['slug' => 'counselor',       'name_ar' => 'مرشد طلابي',         'name_en' => 'Student Counselor', 'sort_order' => 8],
        ];

        foreach ($titles as $t) {
            DB::table('job_titles')->updateOrInsert(
                ['school_id' => null, 'slug' => $t['slug']],
                array_merge($t, ['school_id' => null, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('job_titles')->whereNull('school_id')->delete();
    }
};
