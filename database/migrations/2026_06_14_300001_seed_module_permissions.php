<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed permissions for all modules shown in the job-title permission matrix.
 * Idempotent: uses updateOrInsert keyed on slug.
 *
 * Groups / actions added (existing groups are extended with missing actions):
 *   students, parents, teachers, question_banks, assignments, books,
 *   libraries, evaluations, job_performance, behavior, appointments,
 *   mail, support, reports, settings, pdf_export, noor, whatsapp, job_titles
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $modules = [
            'students' => [
                'view'               => 'عرض الطلاب',
                'create'             => 'إضافة طالب',
                'edit'               => 'تعديل طالب',
                'delete'             => 'حذف طالب',
                'archive'            => 'أرشفة طالب',
                'export'             => 'تصدير بيانات الطلاب',
                'import'             => 'استيراد الطلاب',
                'print'              => 'طباعة بيانات الطلاب',
                'view_details'       => 'عرض تفاصيل الطالب',
                'send_notifications' => 'إرسال إشعارات للطلاب',
            ],
            'parents' => [
                'view'               => 'عرض أولياء الأمور',
                'create'             => 'إضافة ولي أمر',
                'edit'               => 'تعديل ولي أمر',
                'delete'             => 'حذف ولي أمر',
                'export'             => 'تصدير بيانات أولياء الأمور',
                'send_notifications' => 'إرسال إشعارات لأولياء الأمور',
                'send_whatsapp'      => 'إرسال واتساب لأولياء الأمور',
            ],
            'teachers' => [
                'view'               => 'عرض المعلمين',
                'create'             => 'إضافة معلم',
                'edit'               => 'تعديل معلم',
                'delete'             => 'حذف معلم',
                'export'             => 'تصدير بيانات المعلمين',
                'import'             => 'استيراد المعلمين',
                'print'              => 'طباعة بيانات المعلمين',
                'send_notifications' => 'إرسال إشعارات للمعلمين',
            ],
            'question_banks' => [
                'view'   => 'عرض بنك الأسئلة',
                'create' => 'إضافة بنك أسئلة',
                'edit'   => 'تعديل بنك الأسئلة',
                'delete' => 'حذف من بنك الأسئلة',
                'import' => 'استيراد أسئلة',
                'export' => 'تصدير الأسئلة',
            ],
            'assignments' => [
                'view'   => 'عرض الواجبات',
                'create' => 'إضافة واجب',
                'edit'   => 'تعديل واجب',
                'delete' => 'حذف واجب',
                'approve'=> 'اعتماد الواجبات',
            ],
            'books' => [
                'view'   => 'عرض الكتب',
                'create' => 'إضافة كتاب',
                'edit'   => 'تعديل كتاب',
                'delete' => 'حذف كتاب',
                'print'  => 'طباعة قوائم الكتب',
            ],
            'libraries' => [
                'view'   => 'عرض المكتبات',
                'create' => 'إضافة محتوى مكتبة',
                'edit'   => 'تعديل محتوى المكتبة',
                'delete' => 'حذف من المكتبة',
            ],
            'evaluations' => [
                'view'   => 'عرض التقييمات',
                'create' => 'إضافة تقييم',
                'edit'   => 'تعديل التقييم',
                'delete' => 'حذف التقييم',
                'approve'=> 'اعتماد التقييمات',
                'reject' => 'رفض التقييمات',
                'export' => 'تصدير التقييمات',
            ],
            'job_performance' => [
                'view'   => 'عرض تقييم الأداء الوظيفي',
                'create' => 'إضافة تقييم أداء',
                'edit'   => 'تعديل تقييم الأداء',
                'delete' => 'حذف تقييم الأداء',
                'export' => 'تصدير تقييمات الأداء',
            ],
            'behavior' => [
                'view'   => 'عرض سجلات السلوك',
                'create' => 'إضافة سجل سلوك',
                'edit'   => 'تعديل سجل السلوك',
                'delete' => 'حذف سجل السلوك',
                'export' => 'تصدير سجلات السلوك',
            ],
            'appointments' => [
                'view'   => 'عرض المواعيد',
                'create' => 'إضافة موعد',
                'edit'   => 'تعديل الموعد',
                'delete' => 'حذف الموعد',
                'approve'=> 'اعتماد المواعيد',
            ],
            'mail' => [
                'view'               => 'عرض البريد الداخلي',
                'create'             => 'إرسال بريد داخلي',
                'delete'             => 'حذف رسائل البريد',
                'send_notifications' => 'إرسال إشعارات بريدية',
            ],
            'support' => [
                'view'   => 'عرض تذاكر الدعم الفني',
                'create' => 'إضافة تذكرة دعم',
                'edit'   => 'تعديل تذكرة الدعم',
                'delete' => 'حذف تذكرة الدعم',
            ],
            'reports' => [
                'view'   => 'عرض التقارير',
                'export' => 'تصدير التقارير',
                'print'  => 'طباعة التقارير',
            ],
            'settings' => [
                'view'               => 'عرض الإعدادات',
                'edit'               => 'تعديل الإعدادات',
                'manage_permissions' => 'إدارة الصلاحيات',
            ],
            'pdf_export' => [
                'view'  => 'عرض طباعة PDF',
                'print' => 'طباعة PDF وتصدير',
            ],
            'noor' => [
                'view'   => 'عرض بيانات نظام نور',
                'import' => 'استيراد من نور',
                'export' => 'تصدير لنظام نور',
            ],
            'whatsapp' => [
                'view'         => 'عرض واتساب',
                'send_whatsapp'=> 'إرسال رسائل واتساب',
            ],
            'job_titles' => [
                'view'               => 'عرض المسميات الوظيفية',
                'create'             => 'إضافة مسمى وظيفي',
                'edit'               => 'تعديل المسميات الوظيفية',
                'delete'             => 'حذف مسمى وظيفي',
                'manage_permissions' => 'إدارة صلاحيات المسميات الوظيفية',
            ],
        ];

        foreach ($modules as $group => $actions) {
            foreach ($actions as $action => $name) {
                $slug = "{$group}.{$action}";
                DB::table('permissions')->updateOrInsert(
                    ['slug' => $slug],
                    ['name' => $name, 'group' => $group, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        // Only remove groups added by this migration that didn't exist before
        $newGroups = [
            'students', 'parents', 'teachers', 'question_banks', 'assignments',
            'books', 'libraries', 'evaluations', 'job_performance', 'behavior',
            'appointments', 'mail', 'support', 'reports', 'settings',
            'pdf_export', 'noor', 'whatsapp', 'job_titles',
        ];
        foreach ($newGroups as $group) {
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
