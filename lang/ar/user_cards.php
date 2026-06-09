<?php

return [
    // Page
    'page_title'  => 'بطاقات المستخدمين',
    'page_lead'   => 'استخرج بطاقات الدخول للمستخدمين على المنصة بصيغة PDF لتوزيعها بسهولة.',
    'breadcrumb_home' => 'الرئيسية',
    'breadcrumb_users' => 'المستخدمون',
    'breadcrumb_cards' => 'البطاقات',

    // Tabs
    'tab_students_parents' => 'بطاقات الطلاب وأولياء الأمور',
    'tab_staff'            => 'بطاقات المعلمين والإداريين',
    'tab_students_count'   => ':count طالب/ولي أمر',
    'tab_staff_count'      => ':count معلم/إداري',

    // Filters
    'filters_title'   => 'تصفية النتائج',
    'filter_search'   => 'بحث بالاسم أو اسم المستخدم',
    'filter_search_ph'=> 'اكتب اسم المستخدم أو الاسم الكامل…',
    'filter_role'     => 'الدور',
    'filter_school'   => 'المدرسة',
    'filter_grade'    => 'الصف الدراسي',
    'filter_class'    => 'الفصل',
    'filter_job'      => 'المسمى الوظيفي',
    'include_parents' => 'تضمين أولياء الأمور المرتبطين بهؤلاء الطلاب',
    'all'             => 'الكل',
    'apply_filters'   => 'تطبيق التصفية',
    'reset_filters'   => 'إعادة ضبط',

    // Roles in filters
    'role_student'  => 'طالب',
    'role_parent'   => 'ولي أمر',
    'role_teacher'  => 'معلم',
    'role_admin'    => 'إداري',

    // List
    'list_title'           => 'المستخدمون المطابقون',
    'list_empty'           => 'لا يوجد مستخدمون مطابقون لمعايير البحث الحالية.',
    'list_select_all'      => 'تحديد الكل',
    'list_selected_count'  => 'محدد: :count',
    'col_name'             => 'الاسم',
    'col_username'         => 'اسم المستخدم',
    'col_role'             => 'الدور',
    'col_school'           => 'المدرسة',
    'col_grade'            => 'الصف / الفصل',
    'col_job'              => 'المسمى',
    'col_password_status'  => 'كلمة المرور',
    'col_actions'          => 'إجراءات',
    'showing_count'        => 'عرض :count مستخدم',
    'load_more'            => 'تحميل المزيد',

    // Password status
    'pwd_available'    => 'متاحة للطباعة',
    'pwd_unavailable'  => 'غير متاحة',
    'pwd_regenerate'   => 'إصدار جديدة',
    'pwd_regen_help'   => 'إنشاء كلمة مرور جديدة عشوائية للمستخدم وعرضها مرة واحدة لطباعتها.',
    'pwd_regen_confirm'=> 'هل ترغب فعلاً في إعادة إنشاء كلمة مرور للمستخدم :name؟ سيتم تسجيل خروجه من جلسات سابقة.',
    'pwd_regen_success'=> 'تم إنشاء كلمة مرور جديدة بنجاح. كلمة المرور الجديدة: :password',
    'pwd_regen_failed' => 'تعذّر إنشاء كلمة مرور جديدة.',
    'pwd_regen_self_block' => 'لا يمكنك إعادة تعيين كلمة المرور لحسابك أنت من هذه الصفحة.',

    // Buttons
    'btn_print_pdf'       => 'طباعة كملف PDF',
    'btn_print_selected'  => 'طباعة المحددين',
    'btn_print_all_filtered' => 'طباعة كل النتائج',
    'btn_print_one'       => 'طباعة',
    'btn_regenerate'      => 'كلمة مرور جديدة',

    // PDF
    'pdf_username' => 'اسم المستخدم',
    'pdf_password' => 'كلمة المرور',
    'pdf_platform' => 'المنصة',
    'pdf_url'      => 'الرابط',
    'pdf_grade'    => 'الصف',
    'pdf_class'    => 'الفصل',
    'pdf_school'   => 'المدرسة',
    'pdf_job'      => 'المسمى الوظيفي',
    'pdf_role_student'  => 'طالب',
    'pdf_role_parent'   => 'ولي أمر',
    'pdf_role_teacher'  => 'معلم',
    'pdf_role_admin'    => 'إداري',
    'pdf_login_at'      => 'سجِّل دخولك على',
    'pdf_no_password'   => '— غير متاحة —',
    'pdf_credentials'   => 'بيانات الدخول',

    // Notice
    'notice_title'    => 'كيف تعمل البطاقات؟',
    'notice_body'     => 'يتم تخزين كلمة المرور المختارة عند إنشاء المستخدم بشكل مشفّر لإعادة استخدامها هنا فقط. أمّا الحسابات القديمة أو التي غيّر أصحابها كلمة المرور لاحقاً، فاضغط زر "كلمة مرور جديدة" بجوار السطر لإصدار واحدة جديدة وعرضها مرة واحدة.',

    // Toast / flash messages
    'flash_no_users'  => 'لا توجد بيانات للطباعة وفق الفلاتر الحالية.',
];
