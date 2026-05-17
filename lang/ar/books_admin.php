<?php

return [
    'page_title' => 'الكتب',
    'breadcrumb' => 'الكتب',
    'add_book' => 'إضافة كتاب',
    'add_ministry_book' => 'إضافة كتاب وزاري',
    'edit_book' => 'تعديل كتاب',
    'no_records' => 'لم يُعثر على أية سجلات',
    'all' => 'الكل',

    'filters' => [
        'title' => 'البحث',
        'subject' => 'المادة',
        'grade' => 'الصف الدراسي',
        'term' => 'الفصل الدراسي',
        'ministry' => 'كتب وزارية فقط',
        'status' => 'الحالة',
        'apply' => 'تطبيق',
        'reset' => 'إفراغ',
    ],

    'columns' => [
        'title' => 'عنوان الكتاب',
        'grade' => 'الصف الدراسي',
        'subject' => 'المادة',
        'term' => 'الفصل الدراسي',
        'source' => 'مصدر الكتاب',
        'status' => 'الحالة',
        'created_at' => 'تاريخ الإضافة',
        'actions' => 'التحكم',
    ],

    'fields' => [
        'title' => 'العنوان',
        'source' => 'مصدر الكتاب',
        'file' => 'رفع الكتاب (PDF)',
        'external_url' => 'رابط الكتاب',
        'subject' => 'المادة',
        'grade' => 'الصف الدراسي',
        'term' => 'الفصل الدراسي',
        'description' => 'الوصف',
        'cover' => 'غلاف الكتاب',
        'is_ministry' => 'كتاب وزاري',
        'is_active' => 'مُفعّل',
    ],

    'source_file' => 'ملف',
    'source_external' => 'رابط خارجي',
    'status_active' => 'مُفعّل',
    'status_inactive' => 'غير مُفعّل',
    'yes' => 'نعم',
    'no' => 'لا',
    'ministry_yes' => 'وزاري',
    'ministry_no' => '—',
    'grade_label' => 'الصف :n',
    'grade_unspecified' => 'غير محدد',
    'choose_subject' => 'اختر المادة',
    'choose_grade' => 'اختر الصف',
    'choose_term' => 'اختر الفصل',

    'save' => 'حفظ',
    'reset_form' => 'إفراغ الكل',
    'back' => 'عودة',
    'edit' => 'تعديل',
    'delete' => 'حذف',
    'view' => 'عرض',
    'download' => 'تحميل',
    'confirm_delete' => 'هل أنت متأكد من حذف هذا الكتاب؟',

    'flash_created' => 'تمت إضافة الكتاب بنجاح.',
    'flash_updated' => 'تم تعديل بيانات الكتاب بنجاح.',
    'flash_deleted' => 'تم حذف الكتاب.',

    'student' => [
        'page_title' => 'الكتب',
        'empty' => 'لا توجد كتب متاحة حاليًا.',
        'open' => 'عرض',
    ],

    'pdf_help' => 'يُقبل ملف PDF فقط، بحد أقصى 20 ميجابايت.',
    'current_file' => 'الملف الحالي',
    'replace_file_hint' => 'اترك الحقل فارغًا للإبقاء على الملف الحالي.',
];
