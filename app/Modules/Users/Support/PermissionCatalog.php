<?php

namespace App\Modules\Users\Support;

/**
 * Shared permission catalog: the authoritative module → actions map used to
 * render every permission grid in the app (the per-job-title matrix AND the
 * global roles editor). Extracted from JobTitlePermissionsController so both
 * consumers read one source (DRY — see .kiro/specs/roles-permissions/design.md §3.1).
 *
 * Each MODULES entry: 'group_slug' => ['label' => '...', 'actions' => [...action_slugs]].
 * A permission slug is "{group}.{action}" (e.g. exams.create).
 */
final class PermissionCatalog
{
    /**
     * Module definitions for the permission matrix UI.
     */
    public const MODULES = [
        'users'           => ['label' => 'المستخدمون',         'actions' => ['view','create','edit','delete','export','import','print','send_notifications','manage_permissions']],
        'students'        => ['label' => 'الطلاب',             'actions' => ['view','create','edit','delete','archive','export','import','print','view_details','send_notifications']],
        'parents'         => ['label' => 'أولياء الأمور',      'actions' => ['view','create','edit','delete','export','send_notifications','send_whatsapp']],
        'teachers'        => ['label' => 'المعلمون',           'actions' => ['view','create','edit','delete','export','import','print','send_notifications']],
        'schools'         => ['label' => 'المدارس',            'actions' => ['view','create','edit','delete']],
        'subjects'        => ['label' => 'المواد',             'actions' => ['view','create','edit','delete']],
        'question_banks'  => ['label' => 'بنك الأسئلة',       'actions' => ['view','create','edit','delete','archive','approve','reject','import','export']],
        'exams'           => ['label' => 'الاختبارات',         'actions' => ['view','create','edit','delete']],
        'grades'          => ['label' => 'إدارة الدرجات',      'actions' => ['view','create','edit']],
        'weekly-plans'    => ['label' => 'الخطة الأسبوعية',    'actions' => ['view','create','edit','delete','lock']],
        'schedules'       => ['label' => 'الجدول المدرسي',     'actions' => ['view','create','edit','delete']],
        'academic-years'  => ['label' => 'الأعوام الدراسية',   'actions' => ['view','create','edit','delete']],
        'assignments'     => ['label' => 'الواجبات',           'actions' => ['view','create','edit','delete','approve']],
        'books'           => ['label' => 'الكتب',              'actions' => ['view','create','edit','delete','print']],
        'libraries'       => ['label' => 'المكتبات',           'actions' => ['view','create','edit','delete']],
        'evaluations'     => ['label' => 'التقييمات',          'actions' => ['view','create','edit','delete','approve','reject','export']],
        'job_performance' => ['label' => 'تقييم الأداء',       'actions' => ['view','create','edit','delete','export']],
        // NOTE: `attendance` is defined in the Sprint-10 block below (extended action set).
        'behavior'        => ['label' => 'السلوك',             'actions' => ['view','create','edit','delete','export']],
        'appointments'    => ['label' => 'المواعيد',           'actions' => ['view','create','edit','delete','approve']],
        'mail'            => ['label' => 'البريد الداخلي',     'actions' => ['view','create','delete','send_notifications']],
        // NOTE: `support` is defined in the Sprint-10 block below (extended action set).
        'reports'         => ['label' => 'التقارير',           'actions' => ['view','export','print']],
        'settings'        => ['label' => 'الإعدادات',          'actions' => ['view','edit','manage_permissions']],
        'pdf_export'      => ['label' => 'PDF / التصدير',     'actions' => ['view','print']],
        'noor'            => ['label' => 'نظام نور',           'actions' => ['view','import','export']],
        'whatsapp'        => ['label' => 'واتساب',             'actions' => ['view','send_whatsapp','send']],
        'job_titles'      => ['label' => 'المسميات الوظيفية', 'actions' => ['view','create','edit','delete','manage_permissions']],

        // ── عمليات التواصل (Sprint 9) — see .kiro/specs/trello-sprint9-comms-foundation ──
        'announcements'   => ['label' => 'الإعلانات',              'actions' => ['view','create','edit','delete','publish','read_log']],
        'calendar'        => ['label' => 'التقويم المدرسي',       'actions' => ['view','create_event','edit_event','delete_event','print']],
        'virtual_classes' => ['label' => 'الفصول الافتراضية',     'actions' => ['view','create','start','join','view_attendance','recalc_attendance','clear_cache']],
        'discussion'      => ['label' => 'غرف النقاش',            'actions' => ['view','create','edit','delete','toggle_comments']],
        'mailbox'         => ['label' => 'صندوق البريد الداخلي',  'actions' => ['view','send','draft','delete','archive']],
        'sms'             => ['label' => 'الرسائل القصيرة SMS',   'actions' => ['view','send']],
        'messages'        => ['label' => 'خدمات الرسائل',         'actions' => ['send_excel','templates','reports','sender_name','credit']],
        'parents_contact' => ['label' => 'أولياء الأمور كجهة تواصل', 'actions' => ['view','manage']],

        // ── طبقة التصنيفات التعليمية لإعادة بناء بنك الأسئلة (#248) — see .kiro/specs/qb-rebuild-foundation ──
        'compounds'       => ['label' => 'المجمعات',              'actions' => ['view','create','edit','delete']],
        'skills'          => ['label' => 'المهارات',             'actions' => ['view','create','edit','delete','import','export']],
        'weeks'           => ['label' => 'الأسابيع الدراسية',    'actions' => ['view','create','edit','delete']],
        'standards'       => ['label' => 'المعايير',             'actions' => ['view','create','edit','delete']],

        // ── الدورة التشغيلية (Sprint 10, #260) — see .kiro/specs/trello-sprint10-foundation ──
        // `attendance` (student) is EXTENDED on top of the legacy view/create/edit/delete/export.
        // `create` kept for backward-compat (legacy permission_role grants exist) — pure EXTEND.
        'attendance'         => ['label' => 'حضور الطلاب',          'actions' => ['view','create','record_present','record_absent','record_late','record_excuse','edit','delete','add_excuse','add_note','bulk_present','bulk_absent','bulk_late','notify_parent','view_reports','export','print']],
        'teacher_attendance' => ['label' => 'حضور المعلمين',        'actions' => ['view','record_present','record_absent','record_late','record_excuse','record_period','edit','export','send_message']],
        'qr'                 => ['label' => 'خدمات QR للحضور',      'actions' => ['view','create_card','print_card','export_cards','scan','view_log','close_day','group_create','group_edit','group_delete','link_students','link_devices']],
        'certificates'       => ['label' => 'الشهادات',             'actions' => ['view','template_create','template_edit','template_delete','create','edit','delete','issue','upload_file','preview','send','copy_link']],
        // `support` is EXTENDED on top of the legacy view/create/edit/delete.
        // `edit` kept for backward-compat with the pre-Sprint-10 support group — pure EXTEND.
        'support'            => ['label' => 'الدعم الفني',          'actions' => ['view','create','edit','reply','assign','change_status','close','delete','view_attachments']],
        'admissions'         => ['label' => 'القبول والتسجيل',      'actions' => ['view','edit','delete','change_status','schedule','export','copy_link','copy_company_link','edit_school_settings','edit_settings','edit_info','convert_to_student']],
        'educational_sites'  => ['label' => 'المواقع التعليمية',    'actions' => ['view','create','edit','delete','reorder','toggle_active']],
    ];

    public const SCOPE_LABELS = [
        'all'          => 'كل النظام',
        'company'      => 'الشركة',
        'group'        => 'المجمع',
        'school'       => 'المدرسة',
        'stage'        => 'المرحلة',
        'class'        => 'الصف',
        'section'      => 'الفصل',
        'subject'      => 'المادة',
        'own_students' => 'الطلاب المرتبطون فقط',
        'own_subjects' => 'المواد المسندة فقط',
        'own'          => 'بياناته فقط',
    ];

    /**
     * Flatten the catalog into "{group}.{action}" permission slugs.
     *
     * @return string[]
     */
    public static function allSlugs(): array
    {
        $slugs = [];
        foreach (self::MODULES as $group => $def) {
            foreach ($def['actions'] as $action) {
                $slugs[] = "{$group}.{$action}";
            }
        }

        return $slugs;
    }
}
