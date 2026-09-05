<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Modules\Users\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * Seeds a `permissions` row for EVERY slug in PermissionCatalog::allSlugs()
 * (card #348). Previously this seeder only covered ~14 of the ~50 catalog
 * groups: any group missing a Permission row still renders as a checkbox in
 * the roles/job-title matrices (they read PermissionCatalog::MODULES) but
 * silently fails to persist on save, because RoleController::updatePermissions
 * / JobTitlePermissionsController::update only sync slugs that already exist
 * as Permission rows. firstOrCreate keyed on slug: safe to re-run, never
 * touches/removes an existing row.
 */
class PermissionSeeder extends Seeder
{
    /** Arabic labels for action verbs, used only to build a readable `name`. */
    private const ACTION_LABELS = [
        'view' => 'عرض', 'create' => 'إضافة', 'edit' => 'تعديل', 'delete' => 'حذف',
        'archive' => 'أرشفة', 'approve' => 'اعتماد', 'reject' => 'رفض', 'export' => 'تصدير',
        'import' => 'استيراد', 'print' => 'طباعة', 'view_details' => 'عرض التفاصيل',
        'manage_permissions' => 'إدارة الصلاحيات', 'send_notifications' => 'إرسال إشعارات',
        'send_whatsapp' => 'إرسال واتساب', 'publish' => 'نشر', 'read_log' => 'سجل القراءة',
        'create_event' => 'إضافة حدث', 'edit_event' => 'تعديل حدث', 'delete_event' => 'حذف حدث',
        'start' => 'بدء', 'join' => 'انضمام', 'view_attendance' => 'عرض الحضور',
        'recalc_attendance' => 'إعادة احتساب الحضور', 'clear_cache' => 'مسح الذاكرة المؤقتة',
        'toggle_comments' => 'تفعيل/إيقاف التعليقات', 'send' => 'إرسال', 'draft' => 'مسودة',
        'send_excel' => 'إرسال من Excel', 'templates' => 'القوالب', 'reports' => 'التقارير',
        'sender_name' => 'اسم المرسل', 'credit' => 'الرصيد', 'manage' => 'إدارة',
        'record_present' => 'تسجيل حضور', 'record_absent' => 'تسجيل غياب', 'record_late' => 'تسجيل تأخير',
        'record_excuse' => 'تسجيل استئذان', 'record_period' => 'تسجيل حضور حصة',
        'add_excuse' => 'إضافة عذر', 'add_note' => 'إضافة ملاحظة', 'bulk_present' => 'تعيين جماعي للحضور',
        'bulk_absent' => 'تعيين جماعي للغياب', 'bulk_late' => 'تعيين جماعي للتأخير',
        'notify_parent' => 'إشعار ولي الأمر', 'view_reports' => 'عرض التقارير', 'send_message' => 'إرسال رسالة',
        'create_card' => 'إنشاء بطاقة', 'print_card' => 'طباعة بطاقة', 'export_cards' => 'تصدير البطاقات',
        'scan' => 'تشغيل الماسح', 'view_log' => 'عرض السجل', 'close_day' => 'إغلاق اليوم',
        'group_create' => 'إنشاء مجموعة', 'group_edit' => 'تعديل مجموعة', 'group_delete' => 'حذف مجموعة',
        'link_students' => 'ربط الطلاب', 'link_devices' => 'ربط الأجهزة', 'template_create' => 'إضافة قالب',
        'template_edit' => 'تعديل قالب', 'template_delete' => 'حذف قالب', 'issue' => 'إصدار',
        'upload_file' => 'رفع ملف', 'preview' => 'معاينة', 'copy_link' => 'نسخ الرابط',
        'copy_company_link' => 'نسخ رابط الشركة', 'reply' => 'رد', 'assign' => 'تحويل',
        'change_status' => 'تغيير الحالة', 'close' => 'إغلاق', 'view_attachments' => 'عرض المرفقات',
        'schedule' => 'تحديد موعد', 'edit_school_settings' => 'إعدادات المدرسة',
        'edit_settings' => 'إعدادات التسجيل', 'edit_info' => 'معلومات التسجيل',
        'convert_to_student' => 'تحويل إلى طالب', 'reorder' => 'إعادة ترتيب', 'toggle_active' => 'تفعيل/تعطيل',
    ];

    public function run(): void
    {
        foreach (PermissionCatalog::MODULES as $group => $def) {
            foreach ($def['actions'] as $action) {
                Permission::firstOrCreate(
                    ['slug' => "{$group}.{$action}"],
                    [
                        'name'  => $def['label'].' - '.(self::ACTION_LABELS[$action] ?? $action),
                        'group' => $group,
                    ]
                );
            }
        }
    }
}
