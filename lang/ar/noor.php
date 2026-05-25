<?php

return [
    'page_title'           => 'استيراد بيانات نظام نور',
    'breadcrumb'           => 'استيراد من نور',
    'upload_card_title'    => 'رفع ملف اكسل من نظام نور',
    'upload_help'          => 'اختر المدرسة والعام الدراسي ثم ارفع ملف الاكسل المُصدَّر من نظام نور، واضغط «قراءة الملف» لمعاينة البيانات قبل الحفظ.',

    'school_label'         => 'المدرسة',
    'school_choose'        => 'اختر المدرسة',
    'year_label'           => 'العام الدراسي',
    'year_choose'          => 'اختر العام الدراسي',
    'year_current'         => 'الحالي',

    'import_type_label'    => 'نوع البيانات',
    'import_type_choose'   => 'اختر نوع الاستيراد',
    'file_label'           => 'ملف اكسل من نور',
    'file_hint'            => 'الصيغ المسموحة: xlsx, xls, csv — الحد الأقصى ٢٠ ميجابايت',
    'read_file'            => 'قراءة الملف',
    'submit'               => 'إرسال',
    'execute'              => 'تنفيذ الاستيراد',
    'back_to_form'         => 'استيراد ملف آخر',

    'types' => [
        'students'           => 'الطلاب',
        'students_academic'  => 'تحديث بيانات الرقم الأكاديمي',
        'teachers'           => 'المعلمين',
        'admins'             => 'الإداريين',
    ],

    'instructions_title'   => 'طريقة استخراج الملف من نظام نور',
    'instructions_students_title' => 'استيراد الطلاب',
    'instructions_students' => [
        'التقارير ← تقارير الطلاب ← كشف بيانات طلاب المقررات ← عرض ← تصدير Excel',
        'أو من: التقارير ← كشوف ← المبيضات',
        'تنبيه: لو الطلاب مضافين تحت مدارس مختلطة سيتم إضافتهم كذكور، يمكن تعديلهم لاحقًا.',
    ],
    'instructions_teachers_title' => 'استيراد المعلمين والإداريين',
    'instructions_teachers' => [
        'التقارير ← التقارير الإحصائية ← بيانات شاغلو الوظائف',
        'اختر نوع المستخدم (معلم) ← عرض ← تصدير Excel',
    ],
    'instructions_note'    => 'يجب أن يكون الملف بنفس تنسيق نظام نور دون تعديل العناوين.',

    'preview' => [
        'title'        => 'معاينة البيانات قبل الحفظ',
        'table_title'  => 'صفوف الملف',
        'col_status'   => 'الحالة',
        'col_name'     => 'الاسم',
        'col_id'       => 'رقم الهوية',
        'col_grade'    => 'الصف',
        'col_class'    => 'الفصل',
        'col_parent'   => 'ولي الأمر',
        'col_parent_id'=> 'هوية ولي الأمر',
        'reason_duplicate' => 'مكرر داخل نفس الملف',
        'nothing_to_import' => 'لا توجد صفوف صالحة للاستيراد.',
        'status' => [
            'new'       => 'جديد',
            'update'    => 'تحديث',
            'duplicate' => 'مكرر',
            'invalid'   => 'غير صالح',
        ],
    ],

    'result_title'         => 'نتيجة الاستيراد',
    'result_total'         => 'إجمالي الصفوف',
    'result_created'       => 'تم الإنشاء',
    'result_updated'       => 'تم التحديث',
    'result_failed'        => 'لم يُستورد',
    'result_errors_title'  => 'أسباب الفشل',
    'result_row'           => 'الصف',
    'result_reason'        => 'السبب',
    'result_download_errors' => 'تحميل تقرير الأخطاء',
    'result_parents'       => 'أولياء الأمور: تم إنشاء :created وتحديث :updated وربطهم بالطلاب.',

    'history_title'        => 'سجل عمليات الاستيراد السابقة',
    'history_file'         => 'الملف',
    'history_type'         => 'النوع',
    'history_status'       => 'الحالة',
    'history_date'         => 'التاريخ',
    'history_empty'        => 'لا توجد عمليات استيراد سابقة.',

    'errors' => [
        'missing_library'  => 'مكتبة قراءة ملفات Excel غير مثبتة على الخادم. تم حفظ الملف وسيتم معالجته يدويًا.',
        'invalid_file'     => 'الملف المرفوع غير صالح. يجب أن يكون ملف Excel (xlsx, xls, csv).',
        'parse_failed'     => 'تعذّر قراءة الملف، تأكد من أنه ملف Excel صادر من نظام نور.',
        'empty_file'       => 'الملف لا يحتوي على بيانات.',
        'missing_id'       => 'رقم الهوية مفقود.',
        'no_school'        => 'يجب اختيار مدرسة لإضافة المستخدمين إليها.',
        'log_missing'      => 'عملية الاستيراد غير موجودة.',
        'no_preview'       => 'لا توجد بيانات معاينة محفوظة لهذه العملية، يرجى رفع الملف من جديد.',
    ],

    'status' => [
        'pending'    => 'بانتظار المعالجة',
        'processing' => 'جاري المعالجة',
        'previewed'  => 'تمت المعاينة',
        'completed'  => 'مكتمل',
        'failed'     => 'فشل',
    ],
];
