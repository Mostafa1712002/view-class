<?php

return [
    'types' => [
        'rubric'       => 'روبرك (مستويات أداء)',
        'rating_scale' => 'مقياس تقدير',
        'checklist'    => 'قائمة تحقق',
    ],
    'domains' => [
        'teacher'            => 'تقييم معلم',
        'admin'              => 'تقييم إداري',
        'class_visit'        => 'تقييم زيارة صفية',
        'student'            => 'تقييم طالب',
        'parent'             => 'تقييم ولي أمر',
        'school_environment' => 'تقييم بيئة مدرسية',
        'general'            => 'تقييم عام',
        'job_performance'    => 'تقييم أداء وظيفي',
    ],
    'form_status' => [
        'draft'     => 'مسودة',
        'ready'     => 'جاهز للنشر',
        'published' => 'منشور',
        'closed'    => 'مغلق',
        'archived'  => 'مؤرشف',
    ],
    'eval_status' => [
        'draft'            => 'مسودة',
        'completed'        => 'مكتمل',
        'pending_approval' => 'بانتظار الاعتماد',
        'approved'         => 'معتمد',
        'rejected'         => 'مرفوض',
        'needs_review'     => 'يحتاج مراجعة',
        'locked'           => 'مقفل',
    ],
    'visit_status' => [
        'scheduled'        => 'مجدولة',
        'secret'           => 'سرية',
        'teacher_notified' => 'تم إشعار المعلم',
        'in_progress'      => 'قيد التنفيذ',
        'completed'        => 'مكتملة',
        'postponed'        => 'مؤجلة',
        'cancelled'        => 'ملغاة',
        'missed'           => 'لم تتم',
    ],
];
