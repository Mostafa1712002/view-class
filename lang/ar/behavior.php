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

    'point_types' => [
        'add' => 'إضافة',
        'deduct' => 'خصم',
    ],

    'behaviors' => [
        'title' => 'السلوكيات',
        'add' => 'إضافة سلوك',
        'edit' => 'تعديل السلوك',
        'search' => 'ابحث باسم السلوك',
        'empty' => 'لا توجد سلوكيات في هذا التبويب.',
        'choose_group' => 'اختر المجموعة',
        'no_groups' => 'لا توجد مجموعات مفعّلة في هذا التبويب. أضف مجموعة أولًا.',
        'cols' => [
            'name' => 'عنوان السلوك',
            'group' => 'المجموعة',
            'group_type' => 'نوع المجموعة',
            'actions_count' => 'عدد الإجراءات',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'controls' => 'التحكم',
        ],
        'fields' => [
            'group' => 'المجموعة',
            'name' => 'اسم السلوك',
            'description' => 'وصف السلوك',
            'is_active' => 'مفعّل',
        ],
    ],

    'actions_page' => [
        'title' => 'الإجراءات',
        'add' => 'إضافة إجراء',
        'edit' => 'تعديل الإجراء',
        'search' => 'ابحث في وصف الإجراء',
        'empty' => 'لا توجد إجراءات في هذا التبويب.',
        'choose_behavior' => 'اختر السلوك',
        'no_behaviors' => 'لا توجد سلوكيات مفعّلة في هذا التبويب. أضف سلوكًا أولًا.',
        'cols' => [
            'title' => 'عنوان الإجراء',
            'behavior' => 'السلوك المرتبط',
            'points' => 'النقاط',
            'effect' => 'نوع التأثير',
            'notify' => 'إشعار ولي الأمر',
            'followup' => 'يحتاج متابعة',
            'status' => 'الحالة',
            'controls' => 'التحكم',
        ],
        'fields' => [
            'behavior' => 'السلوك',
            'description' => 'وصف الإجراء',
            'points' => 'عدد النقاط',
            'point_type' => 'نوع النقاط',
            'notify_parent' => 'إشعار ولي الأمر',
            'needs_followup' => 'يحتاج متابعة',
            'is_active' => 'مفعّل',
        ],
    ],

    'flash' => [
        'group_created' => 'تمت إضافة مجموعة السلوك',
        'group_updated' => 'تم تحديث مجموعة السلوك',
        'group_deleted' => 'تم حذف مجموعة السلوك',
        'group_has_behaviors' => 'لا يمكن حذف مجموعة مرتبطة بسلوكيات. يمكنك تعطيلها بدلًا من حذفها.',
        'behavior_created' => 'تمت إضافة السلوك',
        'behavior_updated' => 'تم تحديث السلوك',
        'behavior_deleted' => 'تم حذف السلوك',
        'behavior_has_actions' => 'لا يمكن حذف سلوك مرتبط بإجراءات. يمكنك تعطيله بدلًا من حذفه.',
        'action_created' => 'تمت إضافة الإجراء',
        'action_updated' => 'تم تحديث الإجراء',
        'action_deleted' => 'تم حذف الإجراء',
        'record_created' => 'تم تسجيل السلوك',
        'record_deleted' => 'تم حذف السجل',
    ],

    'records' => [
        'title' => 'سجل السلوك',
        'add' => 'تسجيل سلوك',
        'save' => 'حفظ السجل',
        'search' => 'ابحث باسم الطالب/المعلم',
        'empty' => 'لا توجد سجلات سلوك في هذا التبويب.',
        'choose_student' => 'اختر الطالب',
        'choose_teacher' => 'اختر المعلم',
        'choose_behavior' => 'اختر السلوك',
        'choose_behavior_first' => 'اختر السلوك أولًا',
        'choose_action' => 'اختر الإجراء',
        'no_actions' => 'لا توجد إجراءات لهذا السلوك',
        'no_users' => 'لا يوجد مستخدمون في هذا التبويب.',
        'no_behaviors' => 'لا توجد سلوكيات مفعّلة في هذا التبويب.',
        'points_hint' => 'يُحتسب تلقائيًا من الإجراء، ويمكن تعديله إذا لزم.',
        'points_total' => 'إجمالي النقاط',
        'will_notify' => 'سيتم إشعار ولي الأمر عند الحفظ.',
        'fields' => [
            'subject_student' => 'الطالب',
            'subject_teacher' => 'المعلم',
            'behavior' => 'السلوك',
            'action' => 'الإجراء',
            'points' => 'النقاط',
            'note' => 'ملاحظة',
        ],
        'cols' => [
            'subject' => 'الاسم',
            'behavior' => 'السلوك',
            'action' => 'الإجراء',
            'points' => 'النقاط',
            'notified' => 'تم إشعار ولي الأمر',
            'followup' => 'يحتاج متابعة',
            'recorded_by' => 'سجّله',
            'date' => 'التاريخ',
            'controls' => 'التحكم',
        ],
    ],

    'notify' => [
        'title' => 'تسجيل سلوك',
        'body' => 'تم تسجيل سلوك (:behavior) للطالب :student.',
    ],
    'my' => [
        'title' => 'نقاطي وسلوكي',
        'empty' => 'لا توجد سجلات سلوك بعد.',
        'none' => 'لا توجد بيانات نقاط لعرضها.',
    ],
];
