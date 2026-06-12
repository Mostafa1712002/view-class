<?php

/**
 * Phase C (#205) — Arabic translations for educational outcomes module.
 */
return [

    // Page titles
    'page_title'        => 'النواتج التعليمية',
    'create_title'      => 'إضافة ناتج تعليمي',
    'show_title'        => 'تفاصيل الناتج التعليمي',
    'settings_title'    => 'إعدادات طريقة احتساب الناتج التعليمي',

    // Breadcrumb
    'breadcrumb_index'    => 'النواتج التعليمية',
    'breadcrumb_create'   => 'إضافة',
    'breadcrumb_show'     => 'التفاصيل',
    'breadcrumb_settings' => 'إعدادات الاحتساب',

    // Averaging methods
    'methods' => [
        'all_registered'  => 'المسجلون كلهم (الغائب = 0)',
        'attendees_only'  => 'الحاضرون فقط',
        'label'           => 'طريقة الاحتساب',
    ],

    // Source labels
    'sources' => [
        'manual'          => 'إدخال يدوي',
        'imported'        => 'استيراد من ملف',
        'internal'        => 'من المنصة الداخلية',
        'external_alawwal'=> 'منصة الأول',
        'external_qudrat' => 'أنا والقدرات',
    ],

    // Approval status
    'approval_status' => [
        'draft'    => 'مسودة',
        'approved' => 'معتمد',
    ],

    // Table columns
    'columns' => [
        'test_name'      => 'اسم الاختبار',
        'test_date'      => 'تاريخ الاختبار',
        'grade_level'    => 'الصف',
        'class_label'    => 'الفصل',
        'method_used'    => 'طريقة الاحتساب',
        'final_average'  => 'المتوسط النهائي',
        'registered'     => 'المسجلون',
        'present'        => 'الحاضرون',
        'absent'         => 'الغائبون',
        'source'         => 'المصدر',
        'status'         => 'الحالة',
        'actions'        => 'الإجراءات',
        'created_at'     => 'تاريخ الإنشاء',
    ],

    // Form labels
    'fields' => [
        'test_name'              => 'اسم الاختبار',
        'test_type'              => 'نوع الاختبار',
        'test_date'              => 'تاريخ الاختبار',
        'grade_level'            => 'الصف الدراسي',
        'class_label'            => 'الفصل',
        'teacher_id'             => 'المعلم',
        'subject_id'             => 'المادة',
        'educational_company_id' => 'شركة المحتوى',
        'source'                 => 'مصدر البيانات',
        'method'                 => 'طريقة الاحتساب',
        'students'               => 'بيانات الطلاب',
        'reason'                 => 'سبب إعادة الاحتساب',
    ],

    // Settings page
    'settings' => [
        'school_method_label'  => 'طريقة الاحتساب لهذه المدرسة',
        'global_method_label'  => 'الإعداد الافتراضي العام (مدير النظام)',
        'effective_label'      => 'الطريقة المفعّلة حالياً',
        'school_override_note' => 'تُلغي إعداد المدرسة الإعداد العام عند تحديده.',
        'method_warning'       => 'تغيير طريقة الاحتساب لن يؤثر على النواتج المحتسبة مسبقاً إلا بعد إعادة الاحتساب.',
        'global_only_for_super'=> 'تغيير الإعداد العام متاح لمدير النظام فقط.',
    ],

    // Detail view
    'detail' => [
        'recompute_heading' => 'إعادة احتساب',
        'students_heading'  => 'بيانات الطلاب',
        'student_id'        => 'رقم الطالب',
        'score'             => 'الدرجة',
        'status'            => 'الحالة',
        'present'           => 'حاضر',
        'absent'            => 'غائب',
        'approved_locked'   => 'الناتج معتمد — تحتاج لصلاحيات إدارية لإعادة الاحتساب.',
    ],

    // Flash messages
    'flash' => [
        'created'         => 'تمّ احتساب الناتج التعليمي بنجاح.',
        'recomputed'      => 'تمّت إعادة الاحتساب بنجاح.',
        'settings_saved'  => 'تمّ حفظ إعدادات الاحتساب.',
    ],

    // Validation messages
    'validation' => [
        'test_name_required'  => 'اسم الاختبار مطلوب.',
        'students_required'   => 'بيانات الطلاب مطلوبة.',
        'students_min'        => 'يجب إدخال بيانات طالب واحد على الأقل.',
        'student_id_required' => 'رقم الطالب مطلوب.',
        'score_numeric'       => 'الدرجة يجب أن تكون رقماً.',
        'score_min'           => 'الدرجة لا يمكن أن تكون أقل من 0.',
        'score_max'           => 'الدرجة لا يمكن أن تتجاوز 100.',
        'status_invalid'      => 'حالة الطالب يجب أن تكون "حاضر" أو "غائب".',
    ],

    // Errors
    'errors' => [
        'approved_locked' => 'لا يمكن إعادة احتساب ناتج معتمد إلا بصلاحيات إدارية.',
        'not_found'       => 'الناتج التعليمي غير موجود.',
        'access_denied'   => 'لا تملك صلاحية الوصول إلى هذا الناتج.',
    ],

    // Buttons / actions
    'actions' => [
        'add'          => 'إضافة ناتج تعليمي',
        'recompute'    => 'إعادة الاحتساب',
        'settings'     => 'إعدادات الاحتساب',
        'back_to_list' => 'العودة إلى القائمة',
        'save'         => 'احتساب وحفظ',
    ],

    // Empty state
    'empty' => [
        'heading'     => 'لا توجد نواتج تعليمية بعد',
        'description' => 'ابدأ بإضافة ناتج تعليمي جديد لمتابعة متوسطات درجات الطلاب.',
    ],
];
