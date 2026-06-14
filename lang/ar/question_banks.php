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
    'school_shared' => 'مشترك مع :count مدرسة',

    // Status
    'status_active' => 'مفعّل',
    'status_inactive' => 'غير مفعّل',
    'status_under_review' => 'قيد المراجعة',
    'status_archived' => 'مؤرشف',

    // Source
    'source_manual'       => 'داخلي',
    'source_al_awwal'     => 'منصة الأول',
    'source_library'      => 'مكتبة بنوك',
    'source_import'       => 'استيراد Excel',
    'source_ana_qudurat'  => 'أنا والقدرات (قديم)',

    // Category (classification)
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
        'section_sharing' => 'مشاركة البنك مع المدارس',
        'section_education' => 'الربط التعليمي',
        'section_permissions' => 'الصلاحيات',
        'section_future' => 'إعدادات الربط المستقبلي',
        'sharing_hint' => 'حدّد المدارس التي يمكنها رؤية هذا البنك العام',
        'sharing_empty_means_all' => 'إذا لم تحدد أي مدرسة، يصبح البنك متاحًا لكل المدارس على المنصة.',
        'exportable' => 'قابل للتصدير',
        'external_platform' => 'اسم المنصة الخارجية',
        'external_platform_hint' => 'مثال: al_awwal',
        'name_ar' => 'اسم البنك (عربي)',
        'name_en' => 'اسم البنك (إنجليزي)',
        'description' => 'وصف مختصر للبنك',
        'subject_for_bank' => 'المادة الدراسية',
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
        'is_al_awwal_linkable' => 'قابل للربط بمنصة الأول',
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
    'action_approve' => 'اعتماد البنك',
    'action_promote' => 'ترقية إلى عام',
    'action_copy_to_school' => 'نسخ إلى مدرستي',

    // Confirmations
    'confirm_delete' => 'هل أنت متأكد من حذف هذا البنك؟',
    'confirm_approve' => 'هل تريد اعتماد هذا البنك وجعله نشطًا؟',
    'confirm_promote' => 'هل تريد ترقية هذا البنك إلى بنك عام متاح لجميع مدارس الشركة؟',
    'confirm_copy_to_school' => 'هل تريد نسخ الأسئلة المعتمدة من هذا البنك العام إلى مدرستك؟',

    // Tab labels
    'tab_all' => 'الكل',
    'tab_general' => 'عام (شركة)',
    'tab_private' => 'خاص (مدرسة)',
    'tab_under_review' => 'قيد المراجعة',

    // Column labels
    'col_category' => 'التصنيف',
    'col_linkable' => 'قابل للربط',

    // Scope display
    'scope_company' => 'شركة — عام',
    'scope_school' => 'مدرسة — خاص',

    // Approval notice
    'notice_general_approved_only' => 'تظهر للمدارس الأسئلة المعتمدة فقط من البنوك العامة.',

    // Errors / gates
    'error_general_forbidden' => 'لا يمكنك إنشاء أو تعديل بنوك عامة. هذه العملية مخصصة لمدير المدرسة أو المدير العام فقط.',

    // Empty states
    'empty_title' => 'لا توجد بنوك أسئلة بعد',
    'empty_sub' => 'ابدأ بإضافة أول بنك أسئلة من زر "إضافة بنك"',
    'empty_filtered' => 'لا توجد نتائج مطابقة لعوامل التصفية الحالية',

    // Flash messages (re-declared locally so this module is self-contained)
    'flash_created' => 'تم إنشاء بنك الأسئلة بنجاح',
    'flash_updated' => 'تم تحديث بنك الأسئلة بنجاح',
    'flash_deleted' => 'تم حذف بنك الأسئلة',
    'flash_approved' => 'تم اعتماد البنك بنجاح وأصبح نشطًا',
    'flash_promoted' => 'تم ترقية البنك إلى بنك عام بنجاح',
    'flash_copied' => 'تم نسخ الأسئلة المعتمدة إلى بنك جديد في مدرستك',

    // Titles
    'create_title' => 'إنشاء بنك أسئلة جديد',
    'edit_title' => 'تعديل بنك الأسئلة',

    // Subject-driven name
    'subject_for_bank' => 'المادة الدراسية',
    'name_auto_hint' => 'سيتم تسمية البنك تلقائيًا باسم المادة',

    // Batch create
    'batch_create_title' => 'إنشاء بنوك أسئلة دفعة واحدة',
    'batch_create_link' => 'إنشاء دفعة واحدة',
    'batch_grade' => 'الصف الدراسي',
    'batch_term' => 'الفصل الدراسي',
    'batch_subjects' => 'المواد (اختر أكثر من مادة)',
    'batch_preview_count' => 'سيتم إنشاء :count بنك أسئلة',
    'batch_preview_single' => 'سيتم إنشاء بنك أسئلة واحد',
    'batch_submit' => 'إنشاء البنوك',
    'batch_skip_existing' => 'تخطي البنك إذا كان موجودًا مسبقًا لنفس المادة والصف والفصل',
    'batch_view_existing' => 'عرض البنك الموجود',
    'batch_duplicate_warning' => 'يوجد بنك أسئلة لهذه المادة والصف والفصل الدراسي داخل هذا النطاق — سيتم تخطيه.',
    'batch_created_success' => 'تم إنشاء :count بنك أسئلة بنجاح',
    'batch_created_partial' => 'تم إنشاء :created بنك وتم تخطي :skipped بنك موجود مسبقًا',
    'batch_term_placeholder' => 'مثال: الفصل الأول 2025/2026',
];
