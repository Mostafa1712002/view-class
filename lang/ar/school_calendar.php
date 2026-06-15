<?php

return [
    // Titles
    'title'         => 'التقويم المدرسي',
    'create_title'  => 'إضافة حدث',
    'edit_title'    => 'تعديل الحدث',

    // Breadcrumbs
    'breadcrumb_home' => 'الرئيسية',

    // Buttons
    'btn_add'    => 'إضافة حدث',
    'btn_save'   => 'حفظ',
    'btn_cancel' => 'إلغاء',
    'btn_delete' => 'حذف',
    'btn_close'  => 'إغلاق',

    // Fields
    'field_title'      => 'عنوان الحدث',
    'field_type'       => 'نوع الحدث',
    'field_start_date' => 'تاريخ البداية',
    'field_end_date'   => 'تاريخ النهاية',
    'field_all_day'    => 'طوال اليوم',
    'field_start_time' => 'وقت البداية',
    'field_end_time'   => 'وقت النهاية',
    'field_color'      => 'اللون',
    'field_audience'   => 'الجمهور المستهدف',
    'field_location'   => 'الموقع',
    'field_description' => 'الوصف',
    'field_actions'    => 'الإجراءات',

    // Event types
    'type_general'       => 'حدث عام',
    'type_private'       => 'حدث خاص',
    'type_holiday'       => 'إجازة',
    'type_exam'          => 'اختبار',
    'type_meeting'       => 'اجتماع',
    'type_activity'      => 'نشاط',
    'type_admin'         => 'موعد إداري',
    'type_virtual_class' => 'فصل افتراضي',
    'type_alert'         => 'تنبيه',
    'type_occasion'      => 'مناسبة مدرسية',
    'type_other'         => 'أخرى',

    // Audience
    'audience_all'      => 'الجميع',
    'audience_students' => 'الطلاب',
    'audience_parents'  => 'أولياء الأمور',
    'audience_teachers' => 'المعلمون',
    'audience_staff'    => 'الموظفون',

    // Colors
    'color_auto'   => 'تلقائي (حسب النوع)',
    'color_red'    => 'أحمر',
    'color_orange' => 'برتقالي',
    'color_yellow' => 'أصفر',
    'color_green'  => 'أخضر',
    'color_blue'   => 'أزرق',
    'color_purple' => 'بنفسجي',
    'color_gray'   => 'رمادي',

    // Flash messages
    'flash_created' => 'تم إنشاء الحدث بنجاح.',
    'flash_updated' => 'تم تحديث الحدث بنجاح.',
    'flash_deleted' => 'تم حذف الحدث بنجاح.',

    // Print
    'btn_print'    => 'طباعة',
    'print_title'  => 'تقويم المدرسة',
    'print_range'  => 'الفترة',
    'print_day'    => 'طباعة يومي',
    'print_week'   => 'طباعة أسبوعي',
    'print_month'  => 'طباعة شهري',
    'view_day'     => 'يومي',
    'view_week'    => 'أسبوعي',
    'view_month'   => 'شهري',

    // Validation
    'err_end_before_start'      => 'لا يمكن أن يكون تاريخ النهاية قبل تاريخ البداية.',
    'err_end_time_before_start' => 'لا يمكن أن يكون وقت النهاية قبل وقت البداية في نفس اليوم.',

    // Misc
    'upcoming_events'   => 'الأحداث القادمة (3 أشهر)',
    'no_events'         => 'لا توجد أحداث قادمة.',
    'confirm_delete'    => 'هل أنت متأكد من حذف الحدث ":title"؟',
    'access_denied'     => 'غير مصرح لك بالوصول إلى هذا الحدث.',
];
