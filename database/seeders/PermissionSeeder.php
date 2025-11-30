<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // إدارة المستخدمين
            ['name' => 'عرض المستخدمين', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'إضافة مستخدم', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'تعديل مستخدم', 'slug' => 'users.edit', 'group' => 'users'],
            ['name' => 'حذف مستخدم', 'slug' => 'users.delete', 'group' => 'users'],

            // إدارة الأدوار
            ['name' => 'عرض الأدوار', 'slug' => 'roles.view', 'group' => 'roles'],
            ['name' => 'إضافة دور', 'slug' => 'roles.create', 'group' => 'roles'],
            ['name' => 'تعديل دور', 'slug' => 'roles.edit', 'group' => 'roles'],
            ['name' => 'حذف دور', 'slug' => 'roles.delete', 'group' => 'roles'],

            // إدارة المدارس
            ['name' => 'عرض المدارس', 'slug' => 'schools.view', 'group' => 'schools'],
            ['name' => 'إضافة مدرسة', 'slug' => 'schools.create', 'group' => 'schools'],
            ['name' => 'تعديل مدرسة', 'slug' => 'schools.edit', 'group' => 'schools'],
            ['name' => 'حذف مدرسة', 'slug' => 'schools.delete', 'group' => 'schools'],

            // إدارة الأقسام
            ['name' => 'عرض الأقسام', 'slug' => 'sections.view', 'group' => 'sections'],
            ['name' => 'إضافة قسم', 'slug' => 'sections.create', 'group' => 'sections'],
            ['name' => 'تعديل قسم', 'slug' => 'sections.edit', 'group' => 'sections'],
            ['name' => 'حذف قسم', 'slug' => 'sections.delete', 'group' => 'sections'],

            // إدارة الفصول
            ['name' => 'عرض الفصول', 'slug' => 'classes.view', 'group' => 'classes'],
            ['name' => 'إضافة فصل', 'slug' => 'classes.create', 'group' => 'classes'],
            ['name' => 'تعديل فصل', 'slug' => 'classes.edit', 'group' => 'classes'],
            ['name' => 'حذف فصل', 'slug' => 'classes.delete', 'group' => 'classes'],

            // إدارة المواد
            ['name' => 'عرض المواد', 'slug' => 'subjects.view', 'group' => 'subjects'],
            ['name' => 'إضافة مادة', 'slug' => 'subjects.create', 'group' => 'subjects'],
            ['name' => 'تعديل مادة', 'slug' => 'subjects.edit', 'group' => 'subjects'],
            ['name' => 'حذف مادة', 'slug' => 'subjects.delete', 'group' => 'subjects'],

            // إدارة السنوات الدراسية
            ['name' => 'عرض السنوات الدراسية', 'slug' => 'academic-years.view', 'group' => 'academic-years'],
            ['name' => 'إضافة سنة دراسية', 'slug' => 'academic-years.create', 'group' => 'academic-years'],
            ['name' => 'تعديل سنة دراسية', 'slug' => 'academic-years.edit', 'group' => 'academic-years'],
            ['name' => 'حذف سنة دراسية', 'slug' => 'academic-years.delete', 'group' => 'academic-years'],

            // إدارة الجداول
            ['name' => 'عرض الجداول', 'slug' => 'schedules.view', 'group' => 'schedules'],
            ['name' => 'إضافة جدول', 'slug' => 'schedules.create', 'group' => 'schedules'],
            ['name' => 'تعديل جدول', 'slug' => 'schedules.edit', 'group' => 'schedules'],
            ['name' => 'حذف جدول', 'slug' => 'schedules.delete', 'group' => 'schedules'],

            // إدارة الخطط الأسبوعية
            ['name' => 'عرض الخطط الأسبوعية', 'slug' => 'weekly-plans.view', 'group' => 'weekly-plans'],
            ['name' => 'إضافة خطة أسبوعية', 'slug' => 'weekly-plans.create', 'group' => 'weekly-plans'],
            ['name' => 'تعديل خطة أسبوعية', 'slug' => 'weekly-plans.edit', 'group' => 'weekly-plans'],
            ['name' => 'حذف خطة أسبوعية', 'slug' => 'weekly-plans.delete', 'group' => 'weekly-plans'],
            ['name' => 'قفل الخطة الأسبوعية', 'slug' => 'weekly-plans.lock', 'group' => 'weekly-plans'],

            // إدارة الاختبارات
            ['name' => 'عرض الاختبارات', 'slug' => 'exams.view', 'group' => 'exams'],
            ['name' => 'إضافة اختبار', 'slug' => 'exams.create', 'group' => 'exams'],
            ['name' => 'تعديل اختبار', 'slug' => 'exams.edit', 'group' => 'exams'],
            ['name' => 'حذف اختبار', 'slug' => 'exams.delete', 'group' => 'exams'],

            // إدارة الدرجات
            ['name' => 'عرض الدرجات', 'slug' => 'grades.view', 'group' => 'grades'],
            ['name' => 'إدخال الدرجات', 'slug' => 'grades.create', 'group' => 'grades'],
            ['name' => 'تعديل الدرجات', 'slug' => 'grades.edit', 'group' => 'grades'],

            // إدارة الحضور
            ['name' => 'عرض الحضور', 'slug' => 'attendance.view', 'group' => 'attendance'],
            ['name' => 'تسجيل الحضور', 'slug' => 'attendance.create', 'group' => 'attendance'],
            ['name' => 'تعديل الحضور', 'slug' => 'attendance.edit', 'group' => 'attendance'],

            // التقارير
            ['name' => 'عرض التقارير', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name' => 'تصدير التقارير', 'slug' => 'reports.export', 'group' => 'reports'],

            // الإعدادات
            ['name' => 'عرض الإعدادات', 'slug' => 'settings.view', 'group' => 'settings'],
            ['name' => 'تعديل الإعدادات', 'slug' => 'settings.edit', 'group' => 'settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
