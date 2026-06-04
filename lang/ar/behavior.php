<?php

return [
    'search_btn' => 'بحث',
    'yes' => 'نعم',
    'no' => 'لا',
    'confirm_delete' => 'سيتم حذف المجموعة. هل أنت متأكد؟',

    'tabs' => [
        'students' => 'الطلاب',
        'teachers' => 'المعلمون',
    ],

    'types' => [
        'positive' => 'إيجابي',
        'negative' => 'سلبي',
    ],

    'status' => [
        'active' => 'مفعّلة',
        'inactive' => 'غير مفعّلة',
    ],

    'actions' => [
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'disable' => 'تعطيل',
        'enable' => 'تفعيل',
    ],

    'groups' => [
        'title' => 'مجموعات السلوك',
        'add' => 'إضافة مجموعة سلوك',
        'edit' => 'تعديل مجموعة السلوك',
        'search' => 'ابحث باسم المجموعة',
        'empty' => 'لا توجد مجموعات سلوك في هذا التبويب.',
        'cols' => [
            'name' => 'العنوان',
            'type' => 'النوع',
            'available_for_teacher' => 'متاح للمعلم',
            'behaviors' => 'عدد السلوكيات',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'actions' => 'التحكم',
        ],
        'fields' => [
            'name' => 'اسم المجموعة',
            'type' => 'نوع المجموعة',
            'available_for_teacher' => 'السماح للمعلمين باستخدامها',
            'is_active' => 'مفعّلة',
        ],
    ],

    'flash' => [
        'group_created' => 'تمت إضافة مجموعة السلوك',
        'group_updated' => 'تم تحديث مجموعة السلوك',
        'group_deleted' => 'تم حذف مجموعة السلوك',
        'group_has_behaviors' => 'لا يمكن حذف مجموعة مرتبطة بسلوكيات. يمكنك تعطيلها بدلًا من حذفها.',
    ],
];
