<?php

return [
    // Page titles
    'my_tickets_title'         => 'طلبات الدعم',
    'create_ticket_title'      => 'رفع تذكرة جديدة',
    'ticket_detail_title'      => 'تفاصيل التذكرة',
    'admin_tickets_title'      => 'إدارة تذاكر الدعم',
    'admin_ticket_detail_title'=> 'تفاصيل التذكرة — لوحة الإدارة',

    // Breadcrumbs
    'breadcrumb_home'          => 'الرئيسية',
    'breadcrumb_my_tickets'    => 'طلباتي',
    'breadcrumb_new_ticket'    => 'تذكرة جديدة',
    'breadcrumb_admin_tickets' => 'تذاكر الدعم',

    // Fields
    'field_subject'            => 'الموضوع',
    'field_related_student'     => 'الطالب المرتبط',
    'placeholder_select_student' => 'اختر الطالب',
    'count_all'                 => 'الكل',
    'count_open'               => 'جديدة',
    'count_in_progress'        => 'جاري التنفيذ',
    'count_resolved'           => 'تم حلها',
    'count_closed'             => 'مغلقة',
    'field_category'           => 'التصنيف',
    'field_status'             => 'الحالة',
    'field_priority'           => 'الأولوية',
    'field_body'               => 'تفاصيل المشكلة',
    'field_reply_body'         => 'نص الرد',
    'field_created_at'         => 'تاريخ الإنشاء',
    'field_last_reply_at'      => 'آخر رد',
    'field_creator'            => 'مقدم الطلب',
    'field_assigned_to'        => 'المسؤول',
    'field_assign_to_user'     => 'تعيين لمستخدم (ID)',
    'field_actions'            => 'إجراءات',

    // Statuses
    'status_open'              => 'مفتوح',
    'status_in_progress'       => 'قيد المعالجة',
    'status_resolved'          => 'تم الحل',
    'status_closed'            => 'مغلق',

    // Priorities
    'priority_low'             => 'منخفضة',
    'priority_normal'          => 'عادية',
    'priority_high'            => 'عالية',
    'priority_urgent'          => 'عاجلة',

    // Categories
    'category_technical'       => 'تقني',
    'category_academic'        => 'أكاديمي',
    'category_billing'         => 'مالي',
    'category_account'         => 'حساب',
    'category_other'           => 'أخرى',

    // Buttons
    'btn_new_ticket'           => 'رفع تذكرة جديدة',
    'btn_submit_ticket'        => 'إرسال الطلب',
    'btn_send_reply'           => 'إرسال الرد',
    'btn_view'                 => 'عرض',
    'btn_cancel'               => 'إلغاء',
    'btn_assign'               => 'تعيين',
    'btn_update_status'        => 'تحديث الحالة',

    // Sections
    'section_ticket_details'   => 'تفاصيل الطلب',
    'section_replies'          => 'الردود',
    'section_add_reply'        => 'إضافة رد',
    'section_staff_reply'      => 'رد الدعم الفني',
    'section_assign'           => 'تعيين المسؤول',
    'section_change_status'    => 'تغيير الحالة',

    // Filters
    'filter_status'            => 'الحالة',
    'filter_priority'          => 'الأولوية',
    'filter_category'          => 'التصنيف',
    'filter_all'               => 'الكل',
    'filter_apply'             => 'بحث',
    'filter_reset'             => 'إعادة تعيين',

    // Flash messages
    'flash_created'            => 'تم إرسال طلب الدعم بنجاح.',
    'flash_reply_sent'         => 'تم إرسال الرد بنجاح.',
    'flash_assigned'           => 'تم تعيين المسؤول بنجاح.',
    'flash_status_updated'     => 'تم تحديث حالة التذكرة بنجاح.',

    // Confirmations
    'confirm_submit'           => 'هل تريد إرسال هذا الطلب؟',
    'confirm_assign'           => 'هل تريد تعيين هذا المستخدم؟',
    'confirm_status_change'    => 'هل تريد تغيير حالة التذكرة؟',

    // Misc
    'empty_tickets'            => 'لا توجد تذاكر بعد.',
    'ticket_closed_notice'     => 'هذه التذكرة مغلقة ولا يمكن الرد عليها.',
    'badge_staff'              => 'دعم فني',
    'placeholder_select_category' => 'اختر التصنيف',
    'placeholder_user_id'      => 'رقم المستخدم',

    // ─── #267 additions ───────────────────────────────────────────────────────

    // Ticket type (نوع التذكرة)
    'field_type'               => 'نوع التذكرة',
    'placeholder_select_type'  => 'اختر النوع',
    'type_bug'                 => 'خطأ تقني',
    'type_inquiry'             => 'استفسار',
    'type_feature'             => 'طلب تطوير',
    'type_activate_user'       => 'تفعيل مستخدم',
    'type_login_issue'         => 'مشكلة دخول',
    'type_reports_issue'       => 'مشكلة تقارير',
    'type_attendance_issue'    => 'مشكلة حضور وغياب',
    'type_certificates_issue'  => 'مشكلة شهادات',
    'type_registration_issue'  => 'مشكلة تسجيل',

    // Department (القسم)
    'field_department'         => 'القسم',
    'placeholder_select_department' => 'اختر القسم',
    'dept_assignments'         => 'الواجبات',
    'dept_exams'               => 'الاختبارات',
    'dept_virtual_classes'     => 'الفصول الافتراضية',
    'dept_attendance'          => 'الحضور والغياب',
    'dept_messages'            => 'الرسائل',
    'dept_certificates'        => 'الشهادات',
    'dept_admissions'          => 'القبول والتسجيل',
    'dept_support'             => 'الدعم الفني',
    'dept_other'               => 'أخرى',

    // Problem link + attachments
    'field_problem_url'        => 'رابط المشكلة',
    'placeholder_problem_url'  => 'https://...',
    'field_attachment'         => 'المرفق',
    'field_attachments'        => 'المرفقات',
    'btn_download_attachment'  => 'تحميل المرفق',
    'attachment_hint'          => 'الحد الأقصى 5 ميجابايت (صور، PDF، Word، Excel، نصوص، ZIP).',
    'no_attachment'            => 'لا يوجد مرفق',

    // Admin table extra columns
    'field_school'             => 'المدرسة',
    'field_ticket_no'          => 'رقم التذكرة',

    // Stat cards
    'card_open'                => 'جديدة',
    'card_in_progress'         => 'جاري التنفيذ',
    'card_admin_replied'       => 'الأدمن رد',
    'card_user_replied'        => 'المستخدم رد',
    'card_closed'              => 'مغلق',
    'card_of_total'            => 'من الإجمالي',

    // Status log
    'section_status_log'       => 'سجل تغيير الحالة',
    'empty_status_log'         => 'لا توجد تغييرات على الحالة بعد.',
    'empty_replies'            => 'لا توجد ردود بعد.',

    // Actions
    'btn_close'                => 'إغلاق',
    'btn_reopen'               => 'إعادة فتح',
    'btn_delete'               => 'حذف',
    'confirm_close'            => 'هل تريد إغلاق هذه التذكرة؟',
    'confirm_reopen'           => 'هل تريد إعادة فتح هذه التذكرة؟',
    'confirm_delete'           => 'هل تريد حذف هذه التذكرة نهائياً؟',
    'section_actions'          => 'الإجراءات',

    // Flash
    'flash_closed'             => 'تم إغلاق التذكرة.',
    'flash_reopened'           => 'تمت إعادة فتح التذكرة.',
    'flash_deleted'            => 'تم حذف التذكرة.',

    // Filters extra
    'filter_type'              => 'النوع',
    'filter_department'        => 'القسم',

    // Notifications
    'notify_action_view'       => 'عرض التذكرة',
    'notify_created_title'     => 'تذكرة دعم جديدة',
    'notify_staff_reply_title' => 'رد جديد من الدعم الفني',
    'notify_user_reply_title'  => 'رد جديد من المستخدم على التذكرة',
    'notify_status_title'      => 'تغيّرت حالة التذكرة إلى: :status',
    'notify_assigned_title'    => 'تم تحويل تذكرة دعم إليك',
];
