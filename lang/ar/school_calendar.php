<?php

return [
    // Titles
    'title'         => 'التقويم المدرسي',
    'teacher_title' => 'تقويم المعلم',
    'create_title'  => 'إضافة حدث',
    'edit_title'    => 'تعديل الحدث',

    // Teacher calendar legend
    'legend_lesson'        => 'حصة',
    'legend_exam'          => 'اختبار',
    'legend_assignment'    => 'واجب',
    'legend_virtual_class' => 'فصل افتراضي',
    'legend_appointment'   => 'موعد',
    'legend_school_event'  => 'حدث مدرسي',

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

    // Targeting
    'target_heading'   => 'الجمهور المستهدف',
    'target_school'    => 'لكل المدرسة',
    'target_specific'  => 'تعيين مستخدمين',
    'field_school'     => 'المدرسة',
    'school_hint'      => 'اختيار المدرسة يحدّد الفصول والمستخدمين المتاحين.',
    'field_grades'     => 'الصفوف',
    'field_classes'    => 'الفصول',
    'field_users'      => 'المستخدمون المحددون',
    'users_hint'       => 'اضغط Ctrl لاختيار أكثر من مستخدم.',
    'grade'            => 'الصف',

    // Notifications
    'notif_heading'    => 'الإشعارات والتذكير',
    'field_notify'     => 'إرسال إشعار عند الإضافة؟',
    'field_remind'     => 'إرسال تذكير قبل الحدث؟',
    'field_remind_when' => 'وقت التذكير قبل الحدث',
    'minutes'          => 'دقيقة',
    'hours'            => 'ساعة',
    'one_day'          => 'يوم واحد',
    'notif_new_title'    => 'حدث جديد في التقويم',
    'notif_reminder_title' => 'تذكير بحدث قادم',
    'notif_action'     => 'عرض التقويم',

    // Validation
    'err_specific_required' => 'يجب اختيار صفوف أو فصول أو مستخدمين عند تعيين مستخدمين محددين.',

    // Misc
    'upcoming_events'   => 'الأحداث القادمة (3 أشهر)',
    'no_events'         => 'لا توجد أحداث قادمة.',
    'confirm_delete'    => 'هل أنت متأكد من حذف الحدث ":title"؟',
    'access_denied'     => 'غير مصرح لك بالوصول إلى هذا الحدث.',
];
