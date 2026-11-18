<?php

return [

    'page_title'    => 'جدول الاختبارات',
    'plural'        => 'الاختبارات',
    'breadcrumb'    => 'الاختبارات',

    'online_title'   => 'الاختبارات الإلكترونية',
    'manage_online'  => 'إدارة الاختبارات الإلكترونية',
    'schedule_hint'  => 'هذه الصفحة لعرض مواعيد الاختبارات فقط. يتم إنشاء الاختبارات وإضافة أسئلتها ونشرها ومتابعة نتائجها من «الاختبارات» في القياس والتقويم.',
    'schedule_from'  => 'من تاريخ',
    'schedule_to'    => 'إلى تاريخ',
    'schedule_filter'=> 'عرض',
    'schedule_date'  => 'التاريخ',
    'schedule_time'  => 'الوقت',
    'schedule_empty' => 'لا توجد اختبارات مجدولة في هذه الفترة.',

    'tabs' => [
        'class_schedule' => 'الجدول الدراسي',
        'exam_schedule'  => 'جدول الاختبارات',
    ],

    'kpis' => [
        'total'     => 'إجمالي الاختبارات',
        'published' => 'منشور',
        'active'    => 'جارٍ الآن',
        'upcoming'  => 'مجدول قادم',
    ],

    'filters' => [
        'title'        => 'فلاتر البحث',
        'grade_level'  => 'الصف الدراسي',
        'teacher'      => 'المعلم',
        'subject'      => 'المادة',
        'class'        => 'الفصل',
        'type'         => 'النوع',
        'status'       => 'الحالة',
        'all_grades'   => 'كل الصفوف',
        'all_teachers' => 'كل المعلمين',
        'all_subjects' => 'كل المواد',
        'all_classes'  => 'كل الفصول',
        'all_types'    => 'كل الأنواع',
        'all_statuses' => 'كل الحالات',
        'show'         => 'عرض',
        'reset'        => 'مسح',
    ],

    'actions' => [
        'add'         => 'إضافة اختبار',
        'view'        => 'عرض',
        'questions'   => 'الأسئلة',
        'edit'        => 'تعديل',
        'delete'      => 'حذف',
        'delete_confirm' => 'هل أنت متأكد من حذف هذا الاختبار؟',
    ],

    'columns' => [
        'title'       => 'عنوان الاختبار',
        'subject'     => 'المادة',
        'class'       => 'الفصل',
        'teacher'     => 'المعلم',
        'type'        => 'النوع',
        'questions'   => 'الأسئلة',
        'total_marks' => 'الدرجة',
        'start_time'  => 'موعد البدء',
        'status'      => 'الحالة',
        'published'   => 'النشر',
        'actions'     => 'إجراءات',
    ],

    'badges' => [
        'published'   => 'منشور',
        'draft'       => 'مسودة',
    ],

    'empty' => [
        'title'       => 'لا يوجد جدول للاختبارات في الوقت الحالي',
        'subtitle'    => 'لم يتم إنشاء أي اختبار بعد، أو لا توجد اختبارات تطابق الفلاتر المختارة.',
        'cta'         => 'إنشاء أول اختبار',
    ],

];
