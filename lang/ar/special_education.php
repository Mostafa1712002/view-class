<?php

return [
    // Module title & nav
    'title'               => 'التربية الخاصة',
    'breadcrumb_home'     => 'الرئيسية',

    // Buttons
    'btn_add'             => 'إضافة طالب',
    'btn_edit'            => 'تعديل',
    'btn_delete'          => 'حذف',
    'btn_show'            => 'عرض',
    'btn_save'            => 'حفظ',
    'btn_back'            => 'رجوع',
    'btn_add_plan'        => 'إضافة خطة',
    'btn_add_note'        => 'إضافة ملاحظة',

    // Flash messages
    'flash_created'       => 'تم إضافة الطالب بنجاح.',
    'flash_updated'       => 'تم تحديث بيانات الطالب بنجاح.',
    'flash_deleted'       => 'تم حذف الطالب بنجاح.',
    'flash_plan_added'    => 'تمت إضافة الخطة بنجاح.',
    'flash_plan_deleted'  => 'تم حذف الخطة بنجاح.',
    'flash_note_added'    => 'تمت إضافة الملاحظة بنجاح.',
    'flash_note_deleted'  => 'تم حذف الملاحظة بنجاح.',

    // Access
    'access_denied'       => 'غير مصرح لك بالوصول.',

    // Confirm dialogs
    'confirm_delete'      => 'هل أنت متأكد من حذف الطالب؟',
    'confirm_plan_delete' => 'هل أنت متأكد من حذف الخطة؟',
    'confirm_note_delete' => 'هل أنت متأكد من حذف الملاحظة؟',

    // Fields — student
    'field_student'            => 'الطالب',
    'field_category'           => 'الفئة',
    'field_diagnosis'          => 'التشخيص',
    'field_severity'           => 'درجة الشدة',
    'field_assigned_specialist'=> 'المختص المسؤول',
    'field_status'             => 'الحالة',
    'field_notes'              => 'ملاحظات',
    'field_actions'            => 'إجراءات',

    // Fields — plan
    'field_plan_title'         => 'عنوان الخطة',
    'field_goals'              => 'الأهداف',
    'field_accommodations'     => 'التسهيلات والتعديلات',
    'field_start_date'         => 'تاريخ البدء',
    'field_review_date'        => 'تاريخ المراجعة',
    'field_plan_status'        => 'حالة الخطة',

    // Fields — note
    'field_body'               => 'نص الملاحظة',
    'field_note_date'          => 'تاريخ الملاحظة',
    'field_author'             => 'بواسطة',

    // Category labels
    'category_learning_disability' => 'صعوبات التعلم',
    'category_gifted'              => 'الموهوب',
    'category_speech'              => 'صعوبات النطق',
    'category_physical'            => 'إعاقة حركية',
    'category_behavioral'          => 'اضطراب سلوكي',
    'category_visual'              => 'إعاقة بصرية',
    'category_hearing'             => 'إعاقة سمعية',
    'category_other'               => 'أخرى',

    // Student status labels
    'student_status_active'    => 'نشط',
    'student_status_inactive'  => 'غير نشط',
    'student_status_graduated' => 'متخرج',

    // Plan status labels
    'plan_status_draft'        => 'مسودة',
    'plan_status_active'       => 'نشط',
    'plan_status_completed'    => 'مكتمل',

    // Severity labels
    'severity_mild'     => 'خفيف',
    'severity_moderate' => 'متوسط',
    'severity_severe'   => 'شديد',

    // Empty states
    'no_students'   => 'لا يوجد طلاب مسجلون في التربية الخاصة.',
    'no_plans'      => 'لا توجد خطط مضافة بعد.',
    'no_notes'      => 'لا توجد ملاحظات مضافة بعد.',

    // Section headings in show view
    'section_info'  => 'معلومات الطالب',
    'section_plans' => 'الخطط التعليمية الفردية (IEP)',
    'section_notes' => 'الملاحظات التقدمية',

    // Filters
    'filter_all_categories' => 'جميع الفئات',
    'filter_all_statuses'   => 'جميع الحالات',
    'filter_search'         => 'بحث باسم الطالب...',
    'btn_filter'            => 'بحث',
    'btn_reset'             => 'إعادة تعيين',

    // Placeholders
    'select_student'    => 'اختر الطالب',
    'select_category'   => 'اختر الفئة',
    'select_severity'   => 'اختر درجة الشدة',
    'select_specialist' => 'اختر المختص (اختياري)',
    'select_status'     => 'اختر الحالة',
    'select_plan_status'=> 'اختر حالة الخطة',
];
