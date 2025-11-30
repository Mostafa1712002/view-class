<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // مدير النظام - كامل الصلاحيات
        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'مدير النظام',
                'description' => 'صلاحيات كاملة على النظام',
                'is_active' => true,
            ]
        );
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // مدير المدرسة
        $schoolAdmin = Role::updateOrCreate(
            ['slug' => 'school-admin'],
            [
                'name' => 'مدير المدرسة',
                'description' => 'إدارة المدرسة والمستخدمين',
                'is_active' => true,
            ]
        );
        $schoolAdminPermissions = Permission::whereIn('group', [
            'users', 'sections', 'classes', 'subjects', 'academic-years',
            'schedules', 'weekly-plans', 'exams', 'grades', 'attendance', 'reports'
        ])->pluck('id');
        $schoolAdmin->permissions()->sync($schoolAdminPermissions);

        // المعلم
        $teacher = Role::updateOrCreate(
            ['slug' => 'teacher'],
            [
                'name' => 'معلم',
                'description' => 'إدارة الحصص والدرجات والحضور',
                'is_active' => true,
            ]
        );
        $teacherPermissions = Permission::whereIn('slug', [
            'classes.view',
            'subjects.view',
            'schedules.view',
            'weekly-plans.view', 'weekly-plans.create', 'weekly-plans.edit',
            'exams.view', 'exams.create', 'exams.edit',
            'grades.view', 'grades.create', 'grades.edit',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'reports.view',
        ])->pluck('id');
        $teacher->permissions()->sync($teacherPermissions);

        // الطالب
        $student = Role::updateOrCreate(
            ['slug' => 'student'],
            [
                'name' => 'طالب',
                'description' => 'عرض الجداول والدرجات والاختبارات',
                'is_active' => true,
            ]
        );
        $studentPermissions = Permission::whereIn('slug', [
            'schedules.view',
            'weekly-plans.view',
            'exams.view',
            'grades.view',
            'attendance.view',
        ])->pluck('id');
        $student->permissions()->sync($studentPermissions);

        // ولي الأمر
        $parent = Role::updateOrCreate(
            ['slug' => 'parent'],
            [
                'name' => 'ولي أمر',
                'description' => 'متابعة الطالب والتقارير',
                'is_active' => true,
            ]
        );
        $parentPermissions = Permission::whereIn('slug', [
            'schedules.view',
            'grades.view',
            'attendance.view',
            'reports.view',
        ])->pluck('id');
        $parent->permissions()->sync($parentPermissions);
    }
}
