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
    'filter_status'     => 'الحالة',
    'filter_mode'       => 'الطريقة',
    'filter_date_from'  => 'من تاريخ',
    'filter_date_to'    => 'إلى تاريخ',
];
