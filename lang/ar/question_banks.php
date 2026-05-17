<?php

return [
    // Page chrome
    'breadcrumb_home' => 'الرئيسية',
    'breadcrumb_subjects' => 'إدارة المواد',
    'page_title' => 'بنك الأسئلة',

    // KPIs
    'kpi_total' => 'إجمالي البنوك',
    'kpi_public' => 'بنوك عامة',
    'kpi_private' => 'بنوك خاصة',
    'kpi_active' => 'بنوك مفعّلة',

    // Toolbar / buttons
    'add' => 'إضافة بنك',
    'open_library' => 'مكتبة البنوك',
    'search_engine' => 'محرّك البحث',
    'search_hint' => 'ابحث باسم البنك بالعربي أو الإنجليزي',
    'reset' => 'إلغاء التصفية',
    'count_pill' => 'النتائج',

    // Filters
    'filter_visibility' => 'نوع البنك',
    'filter_status' => 'الحالة',
    'filter_source' => 'المصدر',
    'filter_subject' => 'المادة',
    'filter_grade' => 'الصف',
    'filter_creator' => 'المنشئ',
    'filter_all' => 'الكل',

    // Columns
    'col_name' => 'الاسم',
    'col_visibility' => 'النوع',
    'col_school' => 'المدرسة',
    'col_subject' => 'المادة',
    'col_grade' => 'الصف',
    'col_creator' => 'منشئ المحتوى',
    'col_questions_count' => 'عدد الأسئلة',
    'col_status' => 'الحالة',
    'col_source' => 'المصدر',
    'col_created_at' => 'تاريخ الإنشاء',
    'col_actions' => 'التحكم',

    // Visibility
    'visibility_public' => 'عام',
    'visibility_private' => 'خاص',
    'visibility_public_hint' => 'متاح للمنصة بالكامل أو لأكثر من مدرسة',
    'visibility_private_hint' => 'مرتبط بمدرسة واحدة فقط',
    'school_platform' => 'عام على المنصة',

    // Status
    'status_active' => 'مفعّل',
    'status_inactive' => 'غير مفعّل',
    'status_under_review' => 'قيد المراجعة',
    'status_archived' => 'مؤرشف',

    // Source
    'source_manual' => 'يدوي',
    'source_library' => 'مكتبة البنوك',
    'source_import' => 'مستورد',
    'source_ana_qudurat' => 'أنا والقدرات',

    // Category (future Ana w al-Qudurat)
    'category_school' => 'مدرسي',
    'category_qudurat' => 'قدرات',
    'category_verbal' => 'لفظي',
    'category_quantitative' => 'كمي',
    'category_speed_reading' => 'قراءة سريعة',
    'category_none' => 'بدون تصنيف',

    // Grade levels (numeric labels)
    'grade_any' => 'غير محدد',
    'grades' => [
        1 => 'الأول',
        2 => 'الثاني',
        3 => 'الثالث',
        4 => 'الرابع',
        5 => 'الخامس',
        6 => 'السادس',
        7 => 'السابع',
        8 => 'الثامن',
        9 => 'التاسع',
        10 => 'العاشر',
        11 => 'الحادي عشر',
        12 => 'الثاني عشر',
    ],

    // Form sections
    'form' => [
        'section_basic' => 'البيانات الأساسية',
        'section_education' => 'الربط التعليمي',
        'section_permissions' => 'الصلاحيات',
        'section_future' => 'إعدادات الربط المستقبلي',
        'name_ar' => 'اسم البنك (عربي)',
        'name_en' => 'اسم البنك (إنجليزي)',
        'description' => 'وصف مختصر للبنك',
        'visibility' => 'نوع البنك',
        'status' => 'حالة البنك',
        'source' => 'مصدر البنك',
        'grade_level' => 'الصف الدراسي',
        'category_type' => 'تصنيف المحتوى',
        'subjects' => 'المواد المرتبطة',
        'editors' => 'المعلمون المسموح لهم بإضافة الأسئلة',
        'viewers' => 'المعلمون المسموح لهم بالاطلاع فقط',
        'role_none' => 'لا أحد',
        'role_viewer' => 'اطلاع فقط',
        'role_editor' => 'إضافة وتعديل',
        'is_ana_qudurat_linkable' => 'قابل للربط مستقبلًا بمنصة أنا والقدرات',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
    ],

    // Actions
    'action_view_questions' => 'عرض الأسئلة',
    'action_edit' => 'تعديل البنك',
    'action_delete' => 'حذف البنك',
    'action_more' => 'إجراءات أخرى',
    'action_copy' => 'نسخ البنك',
    'action_archive' => 'أرشفة البنك',

    // Confirmations
    'confirm_delete' => 'هل أنت متأكد من حذف هذا البنك؟',

    // Empty states
    'empty_title' => 'لا توجد بنوك أسئلة بعد',
    'empty_sub' => 'ابدأ بإضافة أول بنك أسئلة من زر "إضافة بنك"',
    'empty_filtered' => 'لا توجد نتائج مطابقة لعوامل التصفية الحالية',

    // Flash messages (re-declared locally so this module is self-contained)
    'flash_created' => 'تم إنشاء بنك الأسئلة بنجاح',
    'flash_updated' => 'تم تحديث بنك الأسئلة بنجاح',
    'flash_deleted' => 'تم حذف بنك الأسئلة',

    // Titles
    'create_title' => 'إنشاء بنك أسئلة جديد',
    'edit_title' => 'تعديل بنك الأسئلة',
];
