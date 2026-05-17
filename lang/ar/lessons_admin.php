<?php

return [
    'page_title' => 'الحصص',
    'index_title' => 'إدارة الحصص',
    'breadcrumb_home' => 'الرئيسية',
    'breadcrumb_index' => 'الحصص',
    'breadcrumb_create' => 'إضافة حصة',
    'breadcrumb_edit' => 'تعديل حصة',

    'actions' => [
        'add' => 'إضافة حصة',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'back' => 'عودة',
    ],

    'kpi' => [
        'total' => 'إجمالي الحصص',
        'teachers' => 'عدد المعلمين',
        'subjects' => 'عدد المواد',
        'classes' => 'عدد الفصول',
    ],

    'filter' => [
        'title' => 'تصفية الحصص',
        'section' => 'المرحلة',
        'class' => 'الفصل',
        'teacher' => 'المعلم',
        'subject' => 'المادة',
        'day' => 'اليوم',
        'search' => 'بحث',
        'search_placeholder' => 'ابحث باسم المعلم أو المادة أو الفصل…',
        'apply' => 'تطبيق',
        'reset' => 'إفراغ',
        'all' => 'الكل',
    ],

    'table' => [
        'teacher' => 'المعلم',
        'subject' => 'المادة',
        'section' => 'المرحلة',
        'class' => 'الفصل',
        'day' => 'اليوم',
        'period' => 'الفترة',
        'time' => 'الوقت',
        'room' => 'القاعة',
        'actions' => 'إجراءات',
        'empty_title' => 'لا توجد حصص بعد',
        'empty_hint' => 'اضغط على زر «إضافة حصة» لإنشاء أول حصة في الجدول الدراسي.',
    ],

    'form' => [
        'create_title' => 'إضافة حصة جديدة',
        'edit_title' => 'تعديل الحصة',
        'create_subtitle' => 'اربط المعلم بالمادة والفصل واليوم والفترة الزمنية.',
        'edit_subtitle' => 'يمكنك تعديل بيانات الحصة الحالية.',
        'class' => 'الفصل الدراسي',
        'academic_year' => 'السنة الدراسية',
        'semester' => 'الفصل (الترم)',
        'semester_first' => 'الفصل الأول',
        'semester_second' => 'الفصل الثاني',
        'subject' => 'المادة',
        'teacher' => 'المعلم',
        'day' => 'اليوم',
        'period_number' => 'رقم الفترة',
        'start_time' => 'وقت البداية',
        'end_time' => 'وقت النهاية',
        'room' => 'القاعة (اختياري)',
        'choose' => 'اختر…',
    ],

    'days' => [
        0 => 'الأحد',
        1 => 'الإثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
    ],

    'flash' => [
        'created' => 'تم إضافة الحصة بنجاح',
        'updated' => 'تم تحديث الحصة بنجاح',
        'deleted' => 'تم حذف الحصة بنجاح',
    ],

    'confirm_delete' => 'هل أنت متأكد من حذف هذه الحصة؟',

    'validation' => [
        'class_required' => 'الفصل الدراسي مطلوب',
        'class_invalid' => 'الفصل الدراسي غير صحيح',
        'year_required' => 'السنة الدراسية مطلوبة',
        'semester_required' => 'الفصل الدراسي مطلوب',
        'subject_required' => 'المادة مطلوبة',
        'teacher_required' => 'المعلم مطلوب',
        'day_required' => 'اليوم مطلوب',
        'period_required' => 'رقم الفترة مطلوب',
        'end_after_start' => 'وقت النهاية يجب أن يكون بعد وقت البداية',
    ],

    'errors' => [
        'unauthorized' => 'غير مصرح لك بالوصول إلى هذه الصفحة',
    ],
];
