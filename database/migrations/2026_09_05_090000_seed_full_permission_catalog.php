<?php

use App\Modules\Users\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Card #348 — catalog/seeder gap fix.
 *
 * PermissionSeeder only ever ran once at initial install, so it seeded
 * whatever PermissionCatalog::MODULES held back then (~14 groups). Every
 * group added to the catalog since (by later migrations that only granted
 * roles, or by plain code changes to PermissionCatalog.php) never got a
 * `permissions` row on already-deployed databases. The roles/job-title
 * matrices render every catalog slug as a checkbox regardless (they read
 * PermissionCatalog::MODULES directly), so an unseeded slug shows up, gets
 * ticked, and silently fails to persist — RoleController::updatePermissions
 * / JobTitlePermissionsController::update only sync slugs that already exist
 * as Permission rows.
 *
 * This migration is the one-shot backfill for `migrate --force` on live:
 * insert a `permissions` row for every slug PermissionCatalog::allSlugs()
 * currently defines, including the 5 groups added by this same card
 * (policies, lessons, class_visits, user_cards, surveys — real admin pages
 * reachable from the sidebar that had no permission group at all).
 *
 * Idempotent: updateOrInsert keyed on slug, mirrors
 * 2026_06_16_600000_seed_sprint10_permissions.php. Never deletes a row, so
 * rollback is a no-op — an inserted permission may already be granted to a
 * role/job-title by the time this runs.
 */
return new class extends Migration
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

    public function up(): void
    {
        $now = now();
        $existing = DB::table('permissions')->pluck('slug')->flip();
        $rows = [];

        foreach (PermissionCatalog::MODULES as $group => $def) {
            foreach ($def['actions'] as $action) {
                $slug = "{$group}.{$action}";
                // Insert-only-if-missing: never touch a row that already exists
                // (live may hold a hand-written `name` for it) — this migration
                // only needs to backfill the gap, not re-stamp every row.
                if (isset($existing[$slug])) {
                    continue;
                }
                $rows[] = [
                    'name'       => $def['label'].' - '.(self::ACTION_LABELS[$action] ?? $action),
                    'slug'       => $slug,
                    'group'      => $group,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            // Chunked insertOrIgnore: safe even if two deploys race on the
            // unique `slug` index.
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('permissions')->insertOrIgnore($chunk);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: rows inserted here may already be granted to a
        // role/job-title via permission_role / job_title_permissions by the
        // time of rollback. Re-running up() is safe and idempotent, so there
        // is nothing to reverse.
    }
};
