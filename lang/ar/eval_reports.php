<?php

return [
    // Page titles
    'supervisors_title'          => 'تقرير المشرفين (ملخص)',
    'supervisors_detailed_title' => 'تقرير المشرفين التفصيلي',
    'general_manager_title'      => 'شاشة المدير العام',

    'breadcrumb_reports'         => 'تقارير التقييم',

    // Buttons
    'print'        => 'طباعة',
    'export_csv'   => 'تصدير CSV',
    'show'         => 'عرض',
    'reset'        => 'إعادة تعيين',
    'view'         => 'عرض التقييم',
    'all'          => 'الكل',

    // Filters
    'filters' => [
        'form'           => 'النموذج',
        'company'        => 'الشركة',
        'complex'        => 'المجمع',
        'school'         => 'المدرسة',
        'stage'          => 'المرحلة',
        'section'        => 'الصف',
        'grade'          => 'الفصل',
        'subject'        => 'المادة',
        'specialization' => 'التخصص',
        'supervisor'     => 'المشرف',
        'teacher'        => 'المعلم',
        'evaluator'      => 'المقيِّم',
        'party'          => 'جهة التقييم',
        'role'           => 'الدور',
        'eval_status'    => 'حالة التقييم',
        'visit_status'   => 'حالة الزيارة',
        'period'         => 'الفترة',
        'date_from'      => 'من تاريخ',
        'date_to'        => 'إلى تاريخ',
        'score_from'     => 'الدرجة من',
        'score_to'       => 'الدرجة إلى',
        'has_evidence'   => 'يوجد شواهد؟',
        'has_missing'    => 'يوجد بنود ناقصة؟',
        'yes'            => 'نعم',
        'no'             => 'لا',
    ],

    // KPI tiles — supervisor summary
    'kpis' => [
        'supervisors'      => 'عدد المشرفين',
        'total_visits'     => 'إجمالي الزيارات',
        'total_evals'      => 'إجمالي التقييمات',
        'completed'        => 'مكتملة',
        'incomplete'       => 'غير مكتملة',
        'postponed_visits' => 'زيارات مؤجلة',
        'cancelled_visits' => 'زيارات ملغاة',
        'avg_pct'          => 'متوسط نسبة التقييم',
        'top_supervisor'   => 'أعلى مشرف',
        'low_supervisor'   => 'أقل مشرف',
        'completion_pct'   => 'نسبة الإنجاز العامة',

        // GM
        'teachers'         => 'عدد المعلمين',
        'approved'         => 'معتمدة',
        'pending_approval' => 'بانتظار الاعتماد',
        'highest'          => 'أعلى أداء',
        'lowest'           => 'أقل أداء',
        'avg_performance'  => 'متوسط الأداء',
        'without_evidence' => 'تقييمات بدون شواهد',
        'needs_review'     => 'تحتاج مراجعة',
    ],

    // Table columns
    'cols' => [
        'supervisor'     => 'المشرف',
        'scheduled'      => 'زيارات مجدولة',
        'executed'       => 'زيارات منفذة',
        'not_executed'   => 'غير منفذة',
        'evaluations'    => 'التقييمات',
        'completed'      => 'مكتملة',
        'incomplete'     => 'غير مكتملة',
        'avg_pct'        => 'متوسط النسبة',
        'completion_pct' => 'نسبة الإنجاز',
        'last_visit'     => 'آخر زيارة',

        'form'           => 'النموذج',
        'teacher'        => 'المعلم',
        'school'         => 'المدرسة',
        'complex'        => 'المجمع',
        'stage'          => 'المرحلة',
        'subject'        => 'المادة',
        'specialization' => 'التخصص',
        'form_type'      => 'نوع النموذج',
        'visit_type'     => 'نوع الزيارة',
        'visit_date'     => 'تاريخ الزيارة',
        'eval_date'      => 'تاريخ التقييم',
        'total_score'    => 'الدرجة',
        'percentage'     => 'النسبة',
        'final_score'    => 'الدرجة النهائية',
        'status'         => 'الحالة',
        'teacher_viewed' => 'اطّلع المعلم؟',
        'teacher_commented' => 'علّق المعلم؟',
        'evidence'       => 'الشواهد',
        'notes'          => 'الملاحظات',
        'items'          => 'البنود',
        'party'          => 'جهة التقييم',
        'evaluator'      => 'المقيِّم',
        'dept'           => 'القسم',
        'last_update'    => 'آخر تحديث',
        'actions'        => 'إجراءات',
    ],

    'multiple_evaluators' => 'عدة مقيّمين',
    'na'                  => '—',

    // Empty states
    'empty' => [
        'title'    => 'لا توجد بيانات',
        'subtitle' => 'لا توجد تقييمات أو زيارات مطابقة لعوامل التصفية المحددة.',
    ],
];
