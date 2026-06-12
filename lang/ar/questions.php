<?php

return [
    'page_title' => 'أسئلة بنك الأسئلة',
    'index_title' => 'قائمة الأسئلة',
    'create_title' => 'إضافة سؤال جديد',
    'edit_title' => 'تعديل السؤال',
    'preview_title' => 'معاينة السؤال',
    'add_btn' => 'إضافة سؤال',
    'duplicate_btn' => 'نسخ',
    'copy_suffix' => '(نسخة)',

    'breadcrumb' => [
        'home' => 'الرئيسية',
        'banks' => 'بنوك الأسئلة',
        'questions' => 'الأسئلة',
    ],

    'types' => [
        'mcq' => 'اختيار من متعدد',
        'true_false' => 'صح وخطأ',
        'essay' => 'سؤال إنشائي',
        'matching' => 'توصيل بين عمودين',
        'fill_blank' => 'املأ الفراغات',
        'short' => 'إجابة قصيرة',
    ],

    'difficulty' => [
        '1' => 'سهل',
        '2' => 'متوسط',
        '3' => 'صعب',
    ],

    'status' => [
        'draft' => 'مسودة',
        'pending_review' => 'بانتظار المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'archived' => 'مؤرشف',
        // legacy fallback (pre-card data may still reference it)
        'published' => 'معتمد',
    ],

    'columns' => [
        'lesson' => 'الدرس',
        'creator' => 'أضافه',
        'type' => 'النوع',
        'body' => 'نص السؤال',
        'points' => 'الدرجة',
        'difficulty' => 'الصعوبة',
        'status' => 'الحالة',
        'created_at' => 'تاريخ الإضافة',
        'standard' => 'يحتوي على معيار',
        'actions' => 'التحكم',
        'code' => 'الكود',
        'content_type' => 'نوع المحتوى',
    ],

    'filters' => [
        'title' => 'تصفية',
        'reset' => 'إعادة ضبط',
        'search' => 'ابحث في نص السؤال',
        'type' => 'نوع السؤال',
        'difficulty' => 'مستوى الصعوبة',
        'lesson' => 'الدرس',
        'status' => 'الحالة',
        'all' => 'الكل',
        'code' => 'كود السؤال',
        'content_type' => 'نوع المحتوى',
        'source' => 'المصدر',
        'has_image' => 'به صورة',
        'full_image_only' => 'صورة كاملة فقط',
    ],

    'content_type' => [
        'text'  => 'نصي',
        'image' => 'صورة',
        'mixed' => 'مختلط',
    ],

    'form' => [
        'sections' => [
            'info' => 'معلومات السؤال',
            'answer' => 'معلومات الجواب',
        ],
        'type' => 'نوع السؤال',
        'difficulty' => 'مستوى الصعوبة',
        'points' => 'درجة السؤال',
        'lesson' => 'الربط بالدرس',
        'lesson_placeholder' => 'لا شيء',
        'status' => 'حالة السؤال',
        'attachment' => 'أرفق ملف مع السؤال',
        'attachment_help' => 'صورة / PDF / صوت / فيديو حتى 10 ميجا',
        'remove_attachment' => 'إزالة المرفق الحالي',
        'body_ar' => 'نص السؤال (عربي)',
        'body_en' => 'نص السؤال (إنجليزي — اختياري)',

        'mcq' => [
            'add_option' => 'إضافة جواب',
            'option_n' => 'الاختيار',
            'correct' => 'الإجابة الصحيحة',
            'remove' => 'حذف',
        ],
        'true_false' => [
            'correct' => 'الإجابة الصحيحة',
            'true' => 'صح',
            'false' => 'خطأ',
        ],
        'essay' => [
            'model_answer' => 'الإجابة النموذجية (إرشادية)',
        ],
        'short' => [
            'model_answer' => 'الإجابة النموذجية',
        ],
        'matching' => [
            'add_pair' => 'إضافة زوج',
            'left' => 'العمود الأول',
            'right' => 'العمود الثاني',
            'remove' => 'حذف',
        ],
        'fill_blank' => [
            'add_blank' => 'إضافة فراغ',
            'blank_n' => 'إجابة الفراغ',
            'remove' => 'حذف',
            'hint' => 'اكتب نص السؤال في الأعلى واترك الفراغات تمثَّل بـ ____',
        ],

        'save' => 'حفظ',
        'reset' => 'إفراغ',
        'back' => 'عودة',
        'cancel' => 'إلغاء',
    ],

    'preview' => [
        'difficulty' => 'مستوى الصعوبة',
        'points' => 'درجة السؤال',
        'lesson' => 'الدرس',
        'type' => 'نوع السؤال',
        'body' => 'نص السؤال',
        'answers' => 'الإجابات',
        'correct' => 'الإجابة الصحيحة',
        'model_answer' => 'الإجابة النموذجية',
        'attachment' => 'المرفق',
        'no_attachment' => 'لا يوجد مرفق',
        'open_attachment' => 'فتح المرفق',
        'close' => 'إغلاق',
        'code' => 'كود السؤال',
    ],

    'has_image' => 'له صورة',
    'no_image' => 'لا توجد صورة',

    'flash' => [
        'created' => 'تمت إضافة السؤال',
        'updated' => 'تم تحديث السؤال',
        'deleted' => 'تم حذف السؤال',
        'duplicated' => 'تم نسخ السؤال — يمكنك تعديل النسخة الجديدة الآن',
    ],

    'confirm' => [
        'delete' => 'هل تريد حذف هذا السؤال؟',
    ],

    'empty' => 'لا توجد أسئلة في هذا البنك بعد.',
    'has_standard_yes' => 'نعم',
    'has_standard_no' => 'لا',
    'view_actions' => [
        'edit' => 'تعديل',
        'preview' => 'معاينة',
        'duplicate' => 'نسخ',
        'delete' => 'حذف',
        'settings' => 'إعدادات',
    ],
];
