<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed permissions for the Sprint-9 "عمليات التواصل" (communications) modules so
 * they appear in the Job-Title permission matrix and are enforceable by
 * User::canDo() + the CheckPermission middleware.
 *
 * Idempotent: uses updateOrInsert keyed on slug. Mirrors the pattern of
 * 2026_06_14_300001_seed_module_permissions.php.
 *
 * NOTE: these slugs MUST also be listed in
 * App\Modules\Users\Controllers\JobTitlePermissionsController::MODULES, otherwise
 * the matrix UI won't render them. See .kiro/specs/trello-sprint9-comms-foundation/design.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $modules = [
            'announcements' => [
                'view'      => 'عرض الإعلانات',
                'create'    => 'إضافة إعلان',
                'edit'      => 'تعديل إعلان',
                'delete'    => 'حذف إعلان',
                'publish'   => 'نشر إعلان',
                'read_log'  => 'عرض سجل قراءة الإعلانات',
            ],
            'calendar' => [
                'view'          => 'عرض التقويم المدرسي',
                'create_event'  => 'إضافة حدث',
                'edit_event'    => 'تعديل حدث',
                'delete_event'  => 'حذف حدث',
                'print'         => 'طباعة التقويم',
            ],
            'virtual_classes' => [
                'view'               => 'عرض الفصول الافتراضية',
                'create'             => 'إنشاء فصل افتراضي',
                'start'              => 'بدء الفصل الافتراضي',
                'join'               => 'الانضمام للفصل الافتراضي',
                'view_attendance'    => 'عرض حضور الفصل الافتراضي',
                'recalc_attendance'  => 'إعادة احتساب الحضور',
                'clear_cache'        => 'مسح ذاكرة الحضور المؤقتة',
            ],
            'discussion' => [
                'view'            => 'عرض غرف النقاش',
                'create'          => 'إنشاء غرفة نقاش',
                'edit'            => 'تعديل غرفة النقاش',
                'delete'          => 'حذف غرفة النقاش',
                'toggle_comments' => 'تفعيل/إيقاف التعليقات',
            ],
            'mailbox' => [
                'view'    => 'عرض صندوق البريد الداخلي',
                'send'    => 'إرسال بريد داخلي',
                'draft'   => 'حفظ مسودة بريد',
                'delete'  => 'حذف رسائل البريد',
                'archive' => 'أرشفة رسائل البريد',
            ],
            'sms' => [
                'view' => 'عرض الرسائل القصيرة',
                'send' => 'إرسال رسائل قصيرة SMS',
            ],
            // Extend the existing legacy `whatsapp` group with a `send` action.
            'whatsapp' => [
                'send' => 'إرسال رسائل واتساب',
            ],
            'messages' => [
                'send_excel'  => 'إرسال رسائل من ملف Excel',
                'templates'   => 'إدارة قوالب الرسائل',
                'reports'     => 'تقارير الرسائل وسجلات الإرسال',
                'sender_name' => 'إدارة اسم المرسل',
                'credit'      => 'إدارة رصيد الرسائل',
            ],
            'parents_contact' => [
                'view'   => 'عرض أولياء الأمور كجهة تواصل',
                'manage' => 'إدارة أولياء الأمور كجهة تواصل',
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
        // Remove only comms permission rows that aren't referenced by any grant.
        // The legacy `whatsapp` group is NOT dropped wholesale — only the `send`
        // action this migration added.
        $slugs = [
            'announcements.view', 'announcements.create', 'announcements.edit',
            'announcements.delete', 'announcements.publish', 'announcements.read_log',
            'calendar.view', 'calendar.create_event', 'calendar.edit_event',
            'calendar.delete_event', 'calendar.print',
            'virtual_classes.view', 'virtual_classes.create', 'virtual_classes.start',
            'virtual_classes.join', 'virtual_classes.view_attendance',
            'virtual_classes.recalc_attendance', 'virtual_classes.clear_cache',
            'discussion.view', 'discussion.create', 'discussion.edit',
            'discussion.delete', 'discussion.toggle_comments',
            'mailbox.view', 'mailbox.send', 'mailbox.draft', 'mailbox.delete', 'mailbox.archive',
            'sms.view', 'sms.send',
            'whatsapp.send',
            'messages.send_excel', 'messages.templates', 'messages.reports',
            'messages.sender_name', 'messages.credit',
            'parents_contact.view', 'parents_contact.manage',
        ];

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
