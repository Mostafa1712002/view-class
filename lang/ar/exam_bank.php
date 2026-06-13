<?php

return [
    // Guard: delete when question is used → archived instead
    'guard_used_delete_archived' =>
        'لا يمكن حذف سؤال مستخدم في اختبار — تم أرشفته بدلاً من ذلك.',

    // Guard: edit when question is used in a published/active exam (non-super-admin)
    'guard_used_published_edit_blocked' =>
        'لا يمكن تعديل سؤال مستخدم في اختبار منشور — أنشئ نسخة جديدة.',

    // Guard: edit an approved question without special permission
    'guard_approved_edit_blocked' =>
        'لا يستطيع المستخدم تعديل سؤال معتمد إلا بصلاحية خاصة.',

    // Bank picker page
    'picker_title'       => 'إضافة أسئلة من بنك الأسئلة',
    'picker_bank_label'  => 'البنك',
    'picker_type_label'  => 'النوع',
    'picker_search_ph'   => 'ابحث في نص السؤال',
    'picker_filter_btn'  => 'تصفية',
    'picker_reset_btn'   => 'إعادة ضبط',
    'picker_add_btn'     => 'إضافة المحدد إلى الاختبار',
    'picker_all_banks'   => 'كل البنوك',
    'picker_all_types'   => 'كل الأنواع',
    'picker_empty'       => 'لا توجد أسئلة معتمدة تطابق الفلتر.',
    'picker_none_selected' => 'يرجى تحديد سؤال واحد على الأقل.',
    'from_bank_badge'    => 'من بنك الأسئلة',
];
