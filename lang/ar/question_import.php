<?php

return [
    'page_title'        => 'استيراد الأسئلة من Excel',
    'breadcrumb'        => 'استيراد من Excel',
    'upload_card_title' => 'استيراد الأسئلة من ملف Excel',
    'download_template' => 'تحميل نموذج Excel',
    'upload_help'       => 'حمّل النموذج، أضف أسئلتك، ثم ارفع الملف للمعاينة قبل التنفيذ.',
    'file_label'        => 'ملف Excel',
    'file_hint'         => 'الصيغ المقبولة: .xlsx, .xls, .csv — الحد الأقصى 10 ميجابايت',
    'read_file'         => 'قراءة الملف ومعاينة',

    'columns_title' => 'أعمدة النموذج',
    'col_name'      => 'العمود',
    'col_required'  => 'إلزامي',
    'col_notes'     => 'ملاحظات',

    'col_notes_code'        => 'رمز مرجعي للسؤال',
    'col_notes_text'        => 'يُتخطى إذا كان السؤال صورة كاملة مع كود',
    'col_notes_options'     => 'لأسئلة الاختيار من متعدد فقط',
    'col_notes_explanation' => 'شرح الإجابة (اختياري)',
    'col_notes_grade'       => 'الصف الدراسي (رقم)',
    'col_notes_semester'    => 'الفصل الدراسي (رقم)',

    'history_title'    => 'سجل الاستيراد',
    'history_filename' => 'اسم الملف',
    'history_status'   => 'الحالة',
    'history_total'    => 'الإجمالي',
    'history_imported' => 'مُستورد',
    'history_failed'   => 'فاشل',
    'history_date'     => 'التاريخ',

    'status' => [
        'pending'   => 'في الانتظار',
        'previewed' => 'معاينة',
        'completed' => 'مكتمل',
        'failed'    => 'فاشل',
    ],

    'preview' => [
        'title'          => 'معاينة الاستيراد',
        'summary_valid'  => 'صحيحة',
        'summary_invalid'=> 'بها أخطاء',
        'summary_total'  => 'الإجمالي',
        'summary_bank'   => 'البنك',
        'col_row'        => 'الصف',
        'col_status'     => 'الحالة',
        'col_type'       => 'النوع',
        'col_text'       => 'نص السؤال',
        'col_difficulty' => 'الصعوبة',
        'col_errors'     => 'الأخطاء',
        'back'           => 'رجوع',
        'execute'        => 'تنفيذ الاستيراد',
        'nothing_valid'  => 'لا توجد صفوف صحيحة للاستيراد',
        'status' => [
            'valid'   => 'صحيح',
            'invalid' => 'خطأ',
        ],
    ],

    'result' => [
        'title'            => 'نتيجة الاستيراد',
        'success'          => 'تم استيراد الأسئلة بنجاح.',
        'partial'          => 'اكتمل الاستيراد مع بعض الأخطاء. راجع الأسطر الفاشلة.',
        'total'            => 'الإجمالي',
        'imported'         => 'مُستورد',
        'failed'           => 'فاشل',
        'download_errors'  => 'تحميل ملف الأخطاء',
        'new_import'       => 'استيراد جديد',
        'back_to_questions'=> 'العودة لقائمة الأسئلة',
        'col_row'          => 'الصف',
        'col_errors'       => 'الأخطاء',
        'col_raw'          => 'البيانات الخام',
    ],

    'errors' => [
        'invalid_file'   => 'صيغة الملف غير مدعومة. استخدم .xlsx أو .xls أو .csv',
        'bad_format'     => 'تنسيق الملف غير صحيح. تأكد من تحميل النموذج الرسمي.',
        'parse_failed'   => 'تعذّر قراءة الملف.',
        'missing_library'=> 'مكتبة PhpSpreadsheet غير مثبّتة.',
        'empty_file'     => 'الملف فارغ أو لا يحتوي على بيانات.',
        'batch_missing'  => 'لم يُعثر على سجل الاستيراد.',
        'no_preview'     => 'لا توجد بيانات معاينة. أعد رفع الملف.',
    ],

    'validation' => [
        'invalid_type'         => 'نوع السؤال غير مسموح به: :value',
        'invalid_content_type' => 'نوع المحتوى غير مسموح به: :value',
        'missing_text'         => 'نص السؤال مطلوب.',
        'invalid_difficulty'   => 'مستوى الصعوبة غير صحيح: :value',
        'missing_correct_answer'=> 'الإجابة الصحيحة مطلوبة لهذا النوع من الأسئلة.',
    ],
];
