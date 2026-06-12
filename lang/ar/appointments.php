<?php

return [
    // Page titles
    'page_title'         => 'المواعيد',
    'schedules_title'    => 'جداول المواعيد',
    'schedule_create'    => 'إضافة جدول مواعيد',
    'schedule_edit'      => 'تعديل جدول المواعيد',
    'schedule_show'      => 'تفاصيل جدول المواعيد',
    'settings_title'     => 'إعدادات المواعيد',
    'bookable_roles'     => 'الوظائف القابلة للحجز',

    // Breadcrumbs
    'breadcrumb_home'      => 'الرئيسية',
    'breadcrumb_schedules' => 'جداول المواعيد',
    'breadcrumb_settings'  => 'إعدادات المواعيد',
    'breadcrumb_create'    => 'إضافة',
    'breadcrumb_edit'      => 'تعديل',
    'breadcrumb_show'      => 'تفاصيل',

    // KPIs
    'kpi_total'    => 'إجمالي الجداول',
    'kpi_active'   => 'نشطة',
    'kpi_inactive' => 'غير نشطة',
    'kpi_open'     => 'مفتوحة للحجز',

    // Form fields
    'field_title'            => 'العنوان',
    'field_date_from'        => 'من تاريخ',
    'field_date_to'          => 'إلى تاريخ',
    'field_days'             => 'أيام الأسبوع',
    'field_time_from'        => 'من الساعة',
    'field_time_to'          => 'إلى الساعة',
    'field_slot_minutes'     => 'مدة الموعد (دقيقة)',
    'field_max_appointments' => 'الحد الأقصى للمواعيد',
    'field_location'         => 'الموقع / الغرفة',
    'field_mode'             => 'طريقة التواصل',
    'field_status'           => 'الحالة',
    'field_notes'            => 'ملاحظات',
    'field_booking_open'     => 'فتح الحجز للطلاب',
    'field_owner'            => 'الموظف',

    // Days of week
    'day_sun' => 'الأحد',
    'day_mon' => 'الاثنين',
    'day_tue' => 'الثلاثاء',
    'day_wed' => 'الأربعاء',
    'day_thu' => 'الخميس',
    'day_fri' => 'الجمعة',
    'day_sat' => 'السبت',

    'days' => [
        'sun' => 'الأحد',
        'mon' => 'الاثنين',
        'tue' => 'الثلاثاء',
        'wed' => 'الأربعاء',
        'thu' => 'الخميس',
        'fri' => 'الجمعة',
        'sat' => 'السبت',
    ],

    // Modes
    'mode_in_person' => 'حضوري',
    'mode_call'      => 'مكالمة هاتفية',
    'mode_virtual'   => 'افتراضي',

    'modes' => [
        'in_person' => 'حضوري',
        'call'      => 'مكالمة هاتفية',
        'virtual'   => 'افتراضي',
    ],

    // Statuses
    'status_active'   => 'نشط',
    'status_inactive' => 'غير نشط',
    'status_expired'  => 'منتهي',

    'statuses' => [
        'active'   => 'نشط',
        'inactive' => 'غير نشط',
        'expired'  => 'منتهي',
    ],

    // Table headers
    'table_title'     => 'العنوان',
    'table_owner'     => 'الموظف',
    'table_dates'     => 'الفترة',
    'table_time'      => 'الوقت',
    'table_slot'      => 'مدة الموعد',
    'table_mode'      => 'الطريقة',
    'table_booked'    => 'المحجوز / المتاح',
    'table_status'    => 'الحالة',
    'table_booking'   => 'الحجز',
    'table_actions'   => 'الإجراءات',

    // Actions / Buttons
    'btn_add'         => 'إضافة جدول',
    'btn_edit'        => 'تعديل',
    'btn_delete'      => 'حذف',
    'btn_copy'        => 'نسخ',
    'btn_show'        => 'تفاصيل',
    'btn_save'        => 'حفظ',
    'btn_cancel'      => 'إلغاء',
    'btn_open_booking'  => 'فتح الحجز',
    'btn_close_booking' => 'إغلاق الحجز',
    'btn_add_role'    => 'إضافة وظيفة',

    // Confirms
    'confirm_delete'          => 'هل أنت متأكد من حذف هذا الجدول؟',
    'confirm_copy'            => 'هل تريد نسخ هذا الجدول؟',
    'confirm_toggle_open'     => 'هل تريد فتح الحجز لهذا الجدول؟',
    'confirm_toggle_close'    => 'هل تريد إغلاق الحجز لهذا الجدول؟',
    'confirm_delete_role'     => 'هل أنت متأكد من حذف هذه الوظيفة؟',

    // Flash messages
    'flash_created'          => 'تم إنشاء جدول المواعيد بنجاح.',
    'flash_updated'          => 'تم تحديث جدول المواعيد بنجاح.',
    'flash_deleted'          => 'تم حذف جدول المواعيد.',
    'flash_copied'           => 'تم نسخ الجدول بنجاح.',
    'flash_booking_opened'   => 'تم فتح الحجز لهذا الجدول.',
    'flash_booking_closed'   => 'تم إغلاق الحجز لهذا الجدول.',
    'flash_role_created'     => 'تم إضافة الوظيفة القابلة للحجز.',
    'flash_role_updated'     => 'تم تحديث الوظيفة.',
    'flash_role_deleted'     => 'تم حذف الوظيفة.',
    'flash_role_activated'   => 'تم تفعيل الوظيفة.',
    'flash_role_deactivated' => 'تم إيقاف الوظيفة.',

    // Empty states
    'empty_title'    => 'لا توجد جداول مواعيد بعد',
    'empty_hint'     => 'أضف جدول مواعيد ليتمكن الطلاب وأولياء الأمور من الحجز.',
    'empty_roles_title' => 'لا توجد وظائف قابلة للحجز',
    'empty_roles_hint'  => 'أضف الوظائف التي يمكن حجز مواعيد معها.',

    // Schedule show — bookings placeholder
    'bookings_phase2'   => 'قائمة الحجوزات ستتوفر في المرحلة الثانية.',

    // Slots info
    'slots_unlimited' => 'غير محدود',
    'slots_available' => 'متبقي :n',
    'min_label'       => 'دقيقة',

    // Bookable role fields
    'field_label'       => 'الاسم / الوظيفة',
    'field_target_type' => 'نوع الهدف',
    'field_target_id'   => 'معرّف الهدف (اختياري)',
    'field_sort'        => 'الترتيب',
    'field_is_active'   => 'مفعّلة',

    // Target types
    'target_type_role'           => 'دور (Role)',
    'target_type_job_title'      => 'المسمى الوظيفي',
    'target_type_user'           => 'موظف محدد',
    'target_type_subject_teacher' => 'معلم مادة',

    // Filter
    'filter_title'      => 'تصفية',
    'filter_all'        => 'الكل',
    'filter_apply'      => 'تطبيق',
    'filter_reset'      => 'إعادة ضبط',
    'filter_q'          => 'بحث بالعنوان',
    'filter_q_student'  => 'بحث باسم الطالب',
    'filter_status'     => 'الحالة',
    'filter_mode'       => 'الطريقة',
    'filter_date_from'  => 'من تاريخ',
    'filter_date_to'    => 'إلى تاريخ',

    // ── Phase 2: Booking Flow ──────────────────────────────────────────────

    // Page titles
    'my_bookings_title'     => 'مواعيدي',
    'manage_bookings_title' => 'إدارة الحجوزات',
    'booking_show_title'    => 'تفاصيل الحجز',

    // Booking statuses
    'booking_status_requested' => 'مطلوب',
    'booking_status_confirmed' => 'مؤكد',
    'booking_status_rejected'  => 'مرفوض',
    'booking_status_cancelled' => 'ملغي',
    'booking_status_completed' => 'مكتمل',

    // Form fields — booking
    'field_bookable_role'    => 'الدور / الوظيفة',
    'field_subject'          => 'المادة',
    'field_target_person'    => 'الشخص المعني',
    'field_reason'           => 'السبب',
    'field_appointment_date' => 'تاريخ الموعد',
    'field_appointment_time' => 'وقت الموعد',
    'field_contact_method'   => 'طريقة التواصل',
    'field_attachment'       => 'مرفق (اختياري)',
    'field_child'            => 'الطالب',
    'field_decided_by'       => 'تم القرار بواسطة',
    'field_decision_at'      => 'تاريخ القرار',
    'field_decision_note'    => 'ملاحظة القرار',

    // Table headers — booking
    'table_bookable_role'  => 'الوظيفة',
    'table_target_person'  => 'الشخص المعني',
    'table_date_time'      => 'التاريخ والوقت',
    'table_contact_method' => 'التواصل',
    'table_student'        => 'الطالب',
    'table_booked_by'      => 'الحاجز',

    // Placeholders
    'placeholder_select_role'    => 'اختر الدور',
    'placeholder_select_subject' => 'اختر المادة',
    'placeholder_select_person'  => 'اختر الشخص',
    'placeholder_select_child'   => 'اختر الطالب',
    'placeholder_choose_file'    => 'اختر ملفاً',
    'placeholder_reject_note'    => 'اكتب سبب الرفض...',

    // Sections
    'section_child'   => 'اختيار الطالب',
    'section_role'    => 'اختيار الوظيفة',
    'section_details' => 'تفاصيل الموعد',
    'booking_details_section' => 'تفاصيل الحجز',
    'decision_section'        => 'القرار',
    'decision_action_title'   => 'إجراء',

    // Buttons — booking
    'btn_book_new'          => 'حجز موعد جديد',
    'btn_book_submit'       => 'تأكيد الحجز',
    'btn_cancel_booking'    => 'إلغاء',
    'btn_confirm_booking'   => 'تأكيد الموعد',
    'btn_reject_booking'    => 'رفض الموعد',
    'btn_complete_booking'  => 'تحديد كمكتمل',
    'btn_view_attachment'   => 'عرض المرفق',
    'btn_back_to_list'      => 'العودة للقائمة',

    // Confirms
    'booking_confirm_cancel'  => 'هل تريد إلغاء هذا الموعد؟',
    'booking_confirm_action'  => 'هل تريد تأكيد هذا الموعد؟',
    'booking_complete_action' => 'هل تريد تحديد هذا الموعد كمكتمل؟',
    'booking_reject_action'   => 'هل تريد رفض هذا الموعد؟',

    // Flash messages — booking
    'booking_flash_created'       => 'تم إرسال طلب الموعد بنجاح.',
    'booking_flash_cancelled'     => 'تم إلغاء الموعد.',
    'booking_flash_cancel_denied' => 'لا يمكن إلغاء هذا الموعد.',
    'booking_flash_confirmed'     => 'تم تأكيد الموعد.',
    'booking_flash_rejected'      => 'تم رفض الموعد.',
    'booking_flash_completed'     => 'تم تحديد الموعد كمكتمل.',

    // Empty states — booking
    'booking_empty_title'  => 'لا توجد مواعيد بعد',
    'booking_empty_hint'   => 'احجز موعدك الأول من خلال الزر أدناه.',
    'manage_empty_title'   => 'لا توجد حجوزات بعد',
    'manage_empty_hint'    => 'لم يتم تلقي أي حجوزات حتى الآن.',

    // Labels
    'label_booking'             => 'حجز',
    'label_all_school_bookings' => 'جميع حجوزات المدرسة',
];
