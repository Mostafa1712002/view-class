<?php

return [
    'title' => 'البرامج التعليمية الخاصة',
    'programs' => ['qiyas' => 'القدرات', 'tahsili' => 'التحصيلي', 'nafis' => 'نافس'],
    'subjects' => [
        'qiyas_verbal' => 'القدرات اللفظية', 'qiyas_quant' => 'القدرات الكمية',
        'tahsili_physics' => 'تحصيلي فيزياء', 'tahsili_chemistry' => 'تحصيلي كيمياء',
        'tahsili_biology' => 'تحصيلي أحياء', 'tahsili_math' => 'تحصيلي رياضيات',
        'nafis_reading' => 'نافس قراءة', 'nafis_math' => 'نافس رياضيات', 'nafis_science' => 'نافس علوم',
    ],
    'levels' => [
        'excellent' => 'ممتاز', 'very_good' => 'جيد جداً', 'good' => 'جيد', 'acceptable' => 'مقبول', 'weak' => 'ضعيف',
    ],
    'attempt_status' => [
        'in_progress' => 'قيد التنفيذ', 'completed' => 'مكتمل', 'timed_out' => 'انتهى الوقت', 'auto_ended' => 'أُنهي تلقائياً',
    ],
    'exam_types' => ['single' => 'اختبار مادة واحدة', 'multi' => 'اختبار متعدد المواد'],
    'creator_roles' => ['admin' => 'إدارة', 'teacher' => 'معلم'],
    'difficulty' => ['easy' => 'سهل', 'medium' => 'متوسط', 'hard' => 'صعب'],
    'terms' => ['first' => 'الفصل الأول', 'second' => 'الفصل الثاني', 'both' => 'الفصلان'],
    'buttons' => [
        'save' => 'حفظ', 'cancel' => 'إلغاء', 'add' => 'إضافة', 'edit' => 'تعديل', 'delete' => 'حذف',
        'back' => 'رجوع', 'search' => 'بحث', 'export_excel' => 'تصدير Excel', 'print' => 'طباعة', 'start' => 'ابدأ',
        'finish' => 'إنهاء', 'view' => 'عرض',
    ],
    'not_enrolled' => 'لست مسجلاً في أي برنامج',
    'no_programs_assigned' => 'لم يتم تعيينك لأي برنامج',
    'empty_bank' => 'لم يتم استيراد بنك الأسئلة لهذا البرنامج بعد',
];
