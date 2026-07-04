<?php

return [
    // Page titles
    'page_title'       => 'محتوى المادة',
    'manage_title'     => 'إدارة محتوى المادة',
    'add_title'        => 'إضافة محتوى جديد',

    // Types
    'type_video'       => 'فيديو',
    'type_attachment'  => 'مرفق',
    'type_link'        => 'رابط',

    // Labels
    'label_title'      => 'العنوان',
    'label_type'       => 'النوع',
    'label_url'        => 'الرابط',
    'label_file'       => 'الملف',
    'label_desc'       => 'الوصف',
    'label_published'  => 'منشور',
    'label_available_from'  => 'متاح من',
    'label_available_until' => 'متاح حتى',
    'label_views'      => 'المشاهدات',
    'label_teacher'    => 'المعلم',

    // Actions
    'btn_add'          => 'إضافة محتوى',
    'btn_publish'      => 'نشر',
    'btn_unpublish'    => 'إلغاء النشر',
    'btn_delete'       => 'حذف',
    'btn_download'     => 'تحميل',

    // Flash messages
    'flash' => [
        'created'    => 'تم إضافة المحتوى بنجاح.',
        'published'  => 'تم نشر المحتوى.',
        'unpublished'=> 'تم إلغاء نشر المحتوى.',
        'deleted'    => 'تم حذف المحتوى.',
    ],

    // Errors
    'not_your_subject'   => 'ليس لديك صلاحية إدارة محتوى هذه المادة.',
    'not_enrolled'       => 'هذه المادة غير متاحة لك.',
    'download_forbidden' => 'ليس لديك صلاحية تحميل هذا الملف.',
    'file_not_found'     => 'الملف غير موجود.',

    // Empty states
    'empty'            => 'لا يوجد محتوى لهذه المادة بعد.',
    'empty_videos'     => 'لا توجد مقاطع فيديو بعد.',
    'empty_attachments'=> 'لا توجد مرفقات بعد.',
    'empty_links'      => 'لا توجد روابط بعد.',

    // Confirm
    'confirm_delete'   => 'هل أنت متأكد من حذف هذا المحتوى؟',

    // Student hub sections
    'section_videos'      => 'الفيديوهات',
    'section_attachments' => 'المرفقات والمواد',
    'section_links'       => 'الروابط المفيدة',
    'section_assignments' => 'الواجبات والتكاليف',
    'section_exams'       => 'الاختبارات',
    'section_virtual'     => 'الحصص الافتراضية',
    'section_discussion'  => 'غرف النقاش',
    'section_books'       => 'الكتب والمراجع',

    // Student assignment detail + submission (card #302)
    'assignment_details'          => 'تفاصيل الواجب',
    'assignment_instructions'     => 'التعليمات',
    'assignment_description'      => 'الوصف',
    'assignment_due'              => 'الموعد النهائي',
    'assignment_max_score'        => 'الدرجة القصوى',
    'assignment_status'           => 'الحالة',
    'assignment_open'             => 'فتح الواجب',
    'assignment_your_submission'  => 'إجابتك',
    'assignment_answer'           => 'نص الإجابة',
    'assignment_answer_hint'      => 'اكتب إجابتك هنا (اختياري إذا رفعت ملفًا).',
    'assignment_file'             => 'ملف الإجابة',
    'assignment_current_file'     => 'الملف المرفوع',
    'assignment_submit'           => 'تسليم الواجب',
    'assignment_resubmit'         => 'إعادة التسليم',
    'assignment_submitted'        => 'تم تسليم الواجب بنجاح.',
    'assignment_submitted_at'     => 'تاريخ التسليم',
    'assignment_late'             => 'تسليم متأخر',
    'assignment_score'            => 'الدرجة',
    'assignment_feedback'         => 'ملاحظات المعلم',
    'assignment_closed'           => 'انتهت مدة تسليم هذا الواجب.',
    'assignment_empty_submission' => 'اكتب إجابة أو ارفع ملفًا قبل التسليم.',
    'assignment_already_graded'   => 'تم تقييم الواجب، لا يمكن إعادة التسليم.',
    'assignment_back'             => 'العودة للمادة',
    'assignment_overdue'          => 'انتهى الموعد',
    'discussion_open'             => 'دخول الغرفة',
];
