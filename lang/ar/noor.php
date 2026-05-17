<?php

return [
    'page_title'           => 'استيراد من ملف اكسل نور',
    'breadcrumb'           => 'استيراد من نور',
    'upload_card_title'    => 'رفع ملف اكسل من نظام نور',
    'upload_help'          => 'يرجى اختيار ملف اكسل الذي تم تصديره من نظام نور',
    'import_type_label'    => 'نوع البيانات',
    'import_type_choose'   => 'اختر نوع الاستيراد',
    'file_label'           => 'ملف اكسل من نور',
    'file_hint'            => 'الصيغ المسموحة: xlsx, xls, csv — الحد الأقصى ٢٠ ميجابايت',
    'submit'               => 'إرسال',

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

    'result_title'         => 'نتيجة الاستيراد',
    'result_total'         => 'إجمالي الصفوف',
    'result_created'       => 'تم الاستيراد',
    'result_updated'       => 'تم التحديث',
    'result_failed'        => 'فشل',
    'result_errors_title'  => 'أسباب الفشل',
    'result_row'           => 'الصف',
    'result_reason'        => 'السبب',
    'result_download_errors' => 'تحميل ملف الأخطاء',
    'back_to_form'         => 'استيراد ملف آخر',

    'errors' => [
        'missing_library'  => 'مكتبة قراءة ملفات Excel غير مثبتة على الخادم. تم حفظ الملف وسيتم معالجته يدويًا.',
        'invalid_file'     => 'الملف المرفوع غير صالح. يجب أن يكون ملف Excel (xlsx, xls, csv).',
        'parse_failed'     => 'تعذّر قراءة الملف، تأكد من أنه ملف Excel صادر من نظام نور.',
        'empty_file'       => 'الملف لا يحتوي على بيانات.',
        'missing_id'       => 'رقم الهوية مفقود.',
        'no_school'        => 'لا توجد مدرسة مرتبطة بحسابك لإضافة المستخدمين إليها.',
    ],

    'status' => [
        'pending'    => 'بانتظار المعالجة',
        'processing' => 'جاري المعالجة',
        'completed'  => 'مكتمل',
        'failed'     => 'فشل',
    ],
];
