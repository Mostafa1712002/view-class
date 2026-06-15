<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed permissions for the Sprint-10 foundation (Trello #260) so the operational
 * cycle modules — student attendance, teacher attendance, QR services,
 * certificates, support/tickets, admissions/registration, and educational
 * websites — appear in the Job-Title permission matrix and are enforceable by
 * User::canDo() + the CheckPermission middleware.
 *
 * Slugs mirror the "الصلاحيات المطلوبة" section of card #260 one-for-one.
 *
 * Idempotent: updateOrInsert keyed on slug. Mirrors
 * 2026_06_15_400000_seed_communications_permissions.php.
 *
 * NOTE: these slugs MUST also be listed in
 * App\Modules\Users\Controllers\JobTitlePermissionsController::MODULES, and every
 * new action verb needs an Arabic entry in the matrix blade's $actionLabels map,
 * otherwise the matrix won't render them / will show a raw English slug.
 * See .kiro/specs/trello-sprint10-foundation/design.md.
 *
 * Reconciliation with existing groups (see design.md §2):
 *  - `attendance` (student) is EXTENDED, not replaced: legacy
 *    create/edit/delete/export stay; the granular record/bulk/notify/reports/print
 *    actions are added on top. New attendance code gates on the granular slugs.
 *  - `support` is EXTENDED with reply/assign/change_status/close/view_attachments.
 *  - "إضافة سلوك / لمجموعة طلاب" stays in the existing `behavior` group (not
 *    duplicated here). "تحديد موعد" stays in the existing `appointments` group;
 *    admissions code that schedules an interview gates on `appointments.create`.
 *  - parent-CRM (#269) reuses existing `parents` + `parents_contact`; messaging
 *    (#271) reuses `sms`/`whatsapp`/`mail`/`messages`; export/PDF (#273) reuses
 *    `pdf_export`/`reports`. No new groups for those — see design.md.
 */
return new class extends Migration
{
    /**
     * Single source of truth for the Sprint-10 permission set. MUST stay
     * identical (group + actions) to JobTitlePermissionsController::MODULES.
     */
    private function modules(): array
    {
        return [
            // ── 1. صلاحيات الحضور والغياب للطلاب (EXTEND existing `attendance`) ──
            'attendance' => [
                'view'           => 'عرض حضور الطلاب',
                'record_present' => 'تسجيل حضور يومي',
                'record_absent'  => 'تسجيل غياب يومي',
                'record_late'    => 'تسجيل تأخير يومي',
                'record_excuse'  => 'تسجيل استئذان',
                'edit'           => 'تعديل حالة حضور',
                'delete'         => 'حذف سجل حضور',
                'add_excuse'     => 'إضافة عذر',
                'add_note'       => 'إضافة ملاحظة',
                'bulk_present'   => 'التعيين الجماعي للحضور',
                'bulk_absent'    => 'التعيين الجماعي للغياب',
                'bulk_late'      => 'التعيين الجماعي للتأخير',
                'notify_parent'  => 'إرسال تنبيه لولي الأمر',
                'view_reports'   => 'عرض تقارير الحضور',
                'export'         => 'تصدير تقارير الحضور',
                'print'          => 'طباعة تقارير الحضور',
            ],

            // ── 2. صلاحيات حضور المعلمين (NEW) ──
            'teacher_attendance' => [
                'view'           => 'عرض حضور المعلمين',
                'record_present' => 'تسجيل حضور معلم يومي',
                'record_absent'  => 'تسجيل غياب معلم',
                'record_late'    => 'تسجيل تأخير معلم',
                'record_excuse'  => 'تسجيل استئذان معلم',
                'record_period'  => 'تسجيل حضور معلم لحصة',
                'edit'           => 'تعديل حالة حضور معلم',
                'export'         => 'تصدير حضور المعلمين',
                'send_message'   => 'إرسال رسالة للمعلم',
            ],

            // ── 3. صلاحيات QR (NEW) ──
            'qr' => [
                'view'         => 'عرض خدمات QR',
                'create_card'  => 'إنشاء بطاقة QR',
                'print_card'   => 'طباعة بطاقة QR',
                'export_cards' => 'تصدير بطاقات QR',
                'scan'         => 'تشغيل ماسح QR',
                'view_log'     => 'عرض سجل المسحات',
                'close_day'    => 'إغلاق اليوم',
                'group_create' => 'إنشاء مجموعة حضور',
                'group_edit'   => 'تعديل مجموعة حضور',
                'group_delete' => 'حذف مجموعة حضور',
                'link_students'=> 'ربط الطلاب بالمجموعات',
                'link_devices' => 'ربط أجهزة IoT',
            ],

            // ── 4. صلاحيات الشهادات (NEW) ──
            'certificates' => [
                'view'            => 'عرض الشهادات',
                'template_create' => 'إضافة قالب تصميم',
                'template_edit'   => 'تعديل قالب تصميم',
                'template_delete' => 'حذف قالب تصميم',
                'create'          => 'إضافة شهادة',
                'edit'            => 'تعديل شهادة',
                'delete'          => 'حذف شهادة',
                'issue'           => 'إصدار شهادة',
                'upload_file'     => 'رفع ملف الشهادة',
                'preview'         => 'معاينة الشهادات',
                'send'            => 'إرسال الشهادات',
                'copy_link'       => 'نسخ رابط الشهادة',
            ],

            // ── 5. صلاحيات الدعم الفني (EXTEND existing `support`) ──
            'support' => [
                'view'             => 'عرض التذاكر',
                'create'           => 'إضافة تذكرة',
                'reply'            => 'الرد على تذكرة',
                'assign'           => 'تحويل تذكرة',
                'change_status'    => 'تغيير حالة تذكرة',
                'close'            => 'إغلاق تذكرة',
                'delete'           => 'حذف تذكرة',
                'view_attachments' => 'عرض مرفقات التذاكر',
            ],

            // ── 6. صلاحيات القبول والتسجيل (NEW) ──
            'admissions' => [
                'view'                => 'عرض طلبات القبول',
                'edit'                => 'تعديل طلب قبول',
                'delete'              => 'حذف طلب',
                'change_status'       => 'تغيير حالة الطلب',
                'schedule'            => 'تحديد موعد',
                'export'              => 'تصدير الطلبات',
                'copy_link'           => 'نسخ رابط التسجيل',
                'copy_company_link'   => 'نسخ رابط التسجيل للشركة',
                'edit_school_settings'=> 'تعديل إعدادات المدرسة',
                'edit_settings'       => 'تعديل إعدادات التسجيل',
                'edit_info'           => 'تعديل معلومات التسجيل',
                'convert_to_student'  => 'تحويل طلب قبول إلى طالب',
            ],

            // ── 7. صلاحيات المواقع التعليمية (NEW) ──
            'educational_sites' => [
                'view'          => 'عرض المواقع التعليمية',
                'create'        => 'إضافة موقع تعليمي',
                'edit'          => 'تعديل موقع تعليمي',
                'delete'        => 'حذف موقع تعليمي',
                'reorder'       => 'ترتيب المواقع',
                'toggle_active' => 'تفعيل أو تعطيل موقع',
            ],
        ];
    }

    public function up(): void
    {
        $now = now();

        foreach ($this->modules() as $group => $actions) {
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
        // Build the full slug list, then delete only rows not referenced by any
        // grant (permission_role legacy RBAC or job_title_permissions pivot).
        $slugs = [];
        foreach ($this->modules() as $group => $actions) {
            foreach (array_keys($actions) as $action) {
                $slugs[] = "{$group}.{$action}";
            }
        }

        $ids = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        $inUse = DB::table('permission_role')->whereIn('permission_id', $ids)->count();
        if (DB::getSchemaBuilder()->hasTable('job_title_permissions')) {
            $inUse += DB::table('job_title_permissions')->whereIn('permission_id', $ids)->count();
        }
        if ($inUse === 0) {
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};
