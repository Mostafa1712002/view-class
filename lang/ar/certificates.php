<?php

return [
    // Page titles
    'title'              => 'الشهادات',
    'issue'              => 'إصدار شهادة',
    'admin_title'        => 'إدارة الشهادات',
    'my_title'           => 'شهاداتي',
    'breadcrumb_home'    => 'الرئيسية',
    'breadcrumb_index'   => 'الشهادات',
    'breadcrumb_create'  => 'إصدار شهادة جديدة',
    'breadcrumb_edit'    => 'تعديل الشهادة',

    // Types
    'types' => [
        'student'       => 'طالب',
        'teacher'       => 'معلم',
        'training'      => 'تدريب',
        'appreciation'  => 'تقدير',
    ],

    // Statuses
    'status' => [
        'draft'     => 'مسودة',
        'published' => 'منشورة',
    ],

    // Fields
    'fields' => [
        'type'             => 'نوع الشهادة',
        'title'            => 'عنوان الشهادة',
        'recipient'        => 'المستلم',
        'issued_by'        => 'أُصدرت من قِبَل',
        'issue_date'       => 'تاريخ الإصدار',
        'status'           => 'الحالة',
        'note'             => 'ملاحظات',
        'file'             => 'ملف الشهادة (PDF أو صورة)',
        'actions'          => 'إجراءات',
    ],

    // Actions
    'actions' => [
        'publish'   => 'نشر',
        'edit'      => 'تعديل',
        'delete'    => 'حذف',
        'download'  => 'تحميل',
        'create'    => 'إصدار شهادة',
        'save'      => 'حفظ',
        'cancel'    => 'إلغاء',
    ],

    // Filters
    'filter_type'   => 'نوع الشهادة',
    'filter_q'      => 'بحث (العنوان / المستلم)',
    'filter_all'    => 'الكل',
    'filter_apply'  => 'بحث',
    'filter_reset'  => 'إعادة تعيين',

    // Flash messages
    'flash' => [
        'created'   => 'تم إصدار الشهادة بنجاح.',
        'updated'   => 'تم تحديث الشهادة بنجاح.',
        'published' => 'تم نشر الشهادة بنجاح.',
        'deleted'   => 'تم حذف الشهادة بنجاح.',
    ],

    // Empty states
    'empty' => 'لا توجد شهادات بعد.',
    'empty_my' => 'لا توجد شهادات مرتبطة بحسابك.',

    // Confirm messages
    'confirm_publish' => 'هل تريد نشر هذه الشهادة؟',
    'confirm_delete'  => 'هل تريد حذف هذه الشهادة نهائيًا؟',

    // Choose placeholder
    'choose_type'      => 'اختر نوع الشهادة',
    'choose_status'    => 'اختر الحالة',
    'choose_recipient' => 'اختر المستلم',
];
