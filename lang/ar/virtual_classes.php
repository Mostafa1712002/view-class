<?php

return [
    // Titles
    'title'          => 'الفصول الافتراضية',
    'create_title'   => 'جلسة جديدة',
    'edit_title'     => 'تعديل الجلسة',
    'student_title'  => 'فصولي الافتراضية',

    // Breadcrumbs
    'breadcrumb_home' => 'الرئيسية',

    // Buttons
    'btn_create'      => 'جلسة جديدة',
    'btn_save'        => 'حفظ',
    'btn_edit'        => 'تعديل',
    'btn_show'        => 'عرض',
    'btn_cancel'      => 'إلغاء',
    'btn_cancel_form' => 'تراجع',
    'btn_delete'      => 'حذف',

    // Fields
    'field_title'        => 'عنوان الجلسة',
    'field_teacher'      => 'المعلم',
    'field_scheduled_at' => 'موعد الجلسة',
    'field_duration'     => 'المدة',
    'field_status'       => 'الحالة',
    'field_zoom'         => 'زووم',
    'field_actions'      => 'الإجراءات',
    'field_audience'     => 'الجمهور المستهدف',
    'field_description'  => 'الوصف',
    'field_created_by'   => 'أنشأه',
    'field_created_at'   => 'تاريخ الإنشاء',
    'field_join'         => 'الانضمام',

    // Minutes label
    'minutes' => 'دقيقة',

    // Status labels
    'status_scheduled' => 'مجدولة',
    'status_live'      => 'مباشرة',
    'status_ended'     => 'منتهية',
    'status_cancelled' => 'ملغاة',

    // Audience
    'audience_all'      => 'الجميع',
    'audience_students' => 'الطلاب',
    'audience_parents'  => 'أولياء الأمور',
    'audience_teachers' => 'المعلمون',
    'audience_staff'    => 'الكادر',

    // Zoom
    'zoom_linked'     => 'مرتبطة',
    'zoom_none'       => 'غير مرتبطة',
    'zoom_info'       => 'تفاصيل زووم',
    'zoom_meeting_id' => 'رقم الاجتماع',
    'zoom_passcode'   => 'رمز الدخول',
    'zoom_join'       => 'انضمام',
    'zoom_start'      => 'بدء الجلسة (مضيف)',
    'zoom_not_linked' => 'لم يتم ربط هذه الجلسة بزووم.',

    // Select placeholder
    'select_teacher' => 'اختر المعلم',

    // Flash messages
    'flash_created'         => 'تم إنشاء الجلسة الافتراضية وربطها بزووم.',
    'flash_created_no_zoom' => 'تم إنشاء الجلسة لكن لم يتم الربط بزووم. تحقق من إعدادات الاتصال.',
    'flash_updated'         => 'تم تحديث الجلسة الافتراضية.',
    'flash_cancelled'       => 'تم إلغاء الجلسة الافتراضية.',
    'flash_deleted'         => 'تم حذف الجلسة الافتراضية.',

    // Confirm messages
    'confirm_cancel' => 'هل أنت متأكد من إلغاء هذه الجلسة؟',
    'confirm_delete' => 'هل أنت متأكد من حذف هذه الجلسة نهائياً؟',

    // Empty states
    'empty'         => 'لا توجد جلسات افتراضية حتى الآن.',
    'student_empty' => 'لا توجد جلسات قادمة حالياً.',

    // Student view
    'join_not_yet' => 'لم يحن وقت الانضمام بعد',

    // Tabs (card #234)
    'tab_today'    => 'فصول اليوم',
    'tab_recorded' => 'الفصول المسجلة',
    'tab_old'      => 'الفصول القديمة',
    'tab_all'      => 'جميع الفصول',

    // Platforms
    'field_platform'      => 'المنصة',
    'platform_external'   => 'رابط خارجي',
    'platform_internal'   => 'منصة داخلية',
    'select_platform'     => 'اختر المنصة',
    'field_external_url'  => 'الرابط الخارجي',

    // Class / subject
    'field_class'    => 'الفصل',
    'field_subject'  => 'المادة',
    'select_class'   => 'بدون فصل محدد (للجميع)',
    'select_subject' => 'بدون مادة',

    // Start / join / started
    'field_started'   => 'بدأ المعلم',
    'btn_start'       => 'بدء الفصل',
    'btn_join'        => 'دخول',
    'started_yes'     => 'بدأت',
    'started_no'      => 'لم تبدأ',

    // Attendance window / actions
    'btn_attendance'        => 'عرض الحضور',
    'attendance_title'      => 'الحضور',
    'btn_recalc'            => 'إعادة احتساب الحضور',
    'btn_export'            => 'تصدير إلى CSV',
    'btn_clear_cache'       => 'حذف الكاش',
    'attendance_search'     => 'بحث باسم الطالب…',
    'attendance_empty'      => 'لا يوجد حضور مسجل لهذا الفصل بعد.',

    // Attendance columns
    'att_student'  => 'اسم الطالب',
    'att_teacher'  => 'المعلم',
    'att_subject'  => 'المادة',
    'att_class'    => 'الفصل',
    'att_joined'   => 'وقت الدخول',
    'att_left'     => 'وقت الخروج',
    'att_duration' => 'مدة الحضور (دقيقة)',
    'att_status'   => 'الحالة',

    // Attendance statuses (colored)
    'att_present' => 'حاضر',
    'att_absent'  => 'غائب',
    'att_late'    => 'متأخر',
    'att_partial' => 'حضر جزئيًا',

    // Summary
    'summary_present' => 'حاضر',
    'summary_absent'  => 'غائب',
    'summary_late'    => 'متأخر',
    'summary_partial' => 'جزئي',
    'summary_total'   => 'الإجمالي',
    'summary_none'    => 'لم يتم احتساب الحضور بعد. اضغط «إعادة احتساب الحضور».',

    // Flash
    'flash_recalc'        => 'تم إعادة احتساب الحضور: :present حاضر، :absent غائب.',
    'flash_cache_cleared' => 'تم حذف كاش الفصل الافتراضي.',
    'join_window_hint'    => 'يظهر زر الدخول قبل الموعد بـ 5 دقائق فقط.',
];
