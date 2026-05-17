<?php

/*
 * Card 66 — الخطة الأسبوعية.
 *
 * NEW lang file. Owns admin + ready-notes vocabulary for the weekly plan
 * pages. shell.nav_weekly_plan still owns the sidebar label.
 */

return [
    // Page chrome
    'page_title' => 'الخطة الأسبوعية',
    'breadcrumb' => 'الخطة الأسبوعية',
    'subtitle' => 'متابعة خطط المعلمين الأسبوعية: دروس، أهداف، واجبات، مرفقات، اختبارات وملاحظات.',

    // KPIs
    'kpi_total' => 'إجمالي الخطط',
    'kpi_prepared' => 'تم التحضير',
    'kpi_not_prepared' => 'لم يتم التحضير',
    'kpi_locked' => 'خطط مقفلة',

    // Filters
    'filters_title' => 'البحث المتقدم',
    'filter_grade' => 'الصف الدراسي',
    'filter_class' => 'الفصل',
    'filter_teacher' => 'المعلم',
    'filter_subject' => 'المادة',
    'filter_status' => 'الحالة',
    'filter_all' => '— الكل —',
    'filter_status_prepared' => 'تم التحضير',
    'filter_status_not_prepared' => 'لم يتم التحضير',
    'btn_search' => 'بحث',
    'btn_reset' => 'مسح الفلاتر',

    // Week navigator
    'week_prev' => 'الأسبوع السابق',
    'week_now' => 'الأسبوع الحالي',
    'week_next' => 'الأسبوع التالي',
    'week_range_label' => 'الأسبوع',
    'week_range_from' => 'من',
    'week_range_to' => 'إلى',

    // Toolbar
    'btn_pdf' => 'تحميل PDF',
    'btn_excel' => 'تحميل Excel',
    'btn_excel_soon' => 'قيد التطوير',
    'btn_columns' => 'تخصيص الأعمدة',
    'btn_list_view' => 'عرض كقائمة',
    'btn_grid_view' => 'عرض كشبكة',
    'btn_ready_notes' => 'الملاحظات الجاهزة',
    'btn_add_plan' => 'إضافة خطة',

    // Table headers
    'th_status' => 'الحالة',
    'th_day' => 'اليوم',
    'th_teacher' => 'المعلم',
    'th_subject' => 'المادة',
    'th_class' => 'الفصل',
    'th_lesson' => 'الدرس',
    'th_objectives' => 'الأهداف',
    'th_homework' => 'الواجبات والمهام',
    'th_attachments' => 'المرفقات',
    'th_exams' => 'الاختبارات',
    'th_notes' => 'الملاحظات',
    'th_actions' => 'الإجراءات',

    // Status labels
    'status_prepared' => 'تم التحضير',
    'status_not_prepared' => 'لم يتم التحضير',
    'status_locked' => 'مقفلة',
    'status_unlocked' => 'مفتوحة',

    // Empty
    'empty_title' => 'لا توجد خطط للأسبوع المحدد',
    'empty_hint' => 'يمكن إضافة خطة جديدة من زر "إضافة خطة" أو الانتقال لأسبوع آخر.',

    // Day names (Sun-Thu school week)
    'days' => [
        0 => 'الأحد',
        1 => 'الاثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
    ],

    // ====== Ready notes templates (الملاحظات الجاهزة) ======
    'notes_page_title' => 'الملاحظات الجاهزة',
    'notes_subtitle' => 'قوالب ملاحظات يستخدمها المعلمون والإدارة داخل الخطة الأسبوعية.',
    'notes_back_to_plan' => 'العودة للخطة الأسبوعية',
    'notes_add_btn' => 'إضافة ملاحظة',
    'notes_table_title' => 'الملاحظة',
    'notes_table_actions' => 'التحكم',
    'notes_table_created_by' => 'أُضيفت بواسطة',
    'notes_table_created_at' => 'تاريخ الإضافة',
    'notes_search_placeholder' => 'ابحث في الملاحظات...',
    'notes_empty' => 'لم يُعثر على أية سجلات',
    'notes_modal_title_add' => 'إضافة ملاحظة',
    'notes_modal_title_edit' => 'تعديل الملاحظة',
    'notes_label_title' => 'عنوان الملاحظة (اختياري)',
    'notes_label_body' => 'نص الملاحظة',
    'notes_btn_save' => 'حفظ',
    'notes_btn_cancel' => 'إغلاق',
    'notes_btn_delete' => 'حذف',
    'notes_btn_edit' => 'تعديل',
    'notes_confirm_delete' => 'هل تريد حذف هذه الملاحظة؟',
    'note_created' => 'تمت إضافة الملاحظة بنجاح',
    'note_updated' => 'تم تحديث الملاحظة بنجاح',
    'note_deleted' => 'تم حذف الملاحظة بنجاح',
    'note_body_required' => 'نص الملاحظة مطلوب',
    'note_global_badge' => 'عامة',
];
