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
        'thanks'        => 'شكر',
        'appreciation'  => 'تقدير',
        'general'       => 'عام',
        'grades'        => 'إشعار درجات',
        // Legacy type values kept so old certificates still display.
        'student'       => 'طالب',
        'teacher'       => 'معلم',
        'training'      => 'تدريب',
    ],

    // Statuses
    'status' => [
        'draft'     => 'مسودة',
        'published' => 'منشورة',
    ],

    // Rendered PDF labels
    'pdf' => [
        'presented_to' => 'تُمنح هذه الشهادة إلى',
        'signature'    => 'التوقيع',
        'seal'         => 'شهادة',
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
        'school'           => 'المدرسة',
        'grade'            => 'الصف',
        'template'         => 'التصميم',
        'progress'         => 'نسبة التنفيذ',
        'students'         => 'الطلاب',
        'signer_name'      => 'اسم الموقّع',
    ],

    // Design / signature block
    'design' => [
        'signature'           => 'التوقيع والتصميم',
        'signature_type'      => 'نوع التوقيع',
        'signature_manual'    => 'يدوي',
        'signature_file'      => 'من ملف',
        'signature_clear'     => 'مسح',
        'signature_draw_hint' => 'ارسم التوقيع في المربع أدناه.',
        'logo'                => 'الشعار',
        'stamp'               => 'الختم',
        'image_hint'          => 'الصيغ: jpg, jpeg, png, webp — أقل من 1.5 ميجا — الأبعاد المفضلة للخلفية 3508×2479.',
        'body'                => 'محتوى الشهادة (نص حر)',
        'bold'                => 'عريض',
        'italic'              => 'مائل',
        'underline'           => 'تسطير',
    ],

    // Grades table (appreciation / grades certificates)
    'grades_table' => [
        'subject' => 'المادة',
        'score'   => 'الدرجة',
        'label'   => 'التقدير',
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
        'issued'    => 'تم إصدار :count شهادة بنجاح.',
    ],

    // Design templates
    'tpl' => [
        'index_title'   => 'قوالب التصاميم',
        'create_title'  => 'إضافة قالب تصميم',
        'edit_title'    => 'تعديل قالب تصميم',
        'back'          => 'عودة',
        'add'           => 'إضافة +',
        'name'          => 'اسم التصميم',
        'type'          => 'التصميم / النوع',
        'orientation'   => 'شكل العرض',
        'background'    => 'الخلفية',
        'background_hint' => 'الصيغ: jpg, jpeg, png, webp — الحجم أقل من 1.5 ميجا — الأبعاد المفضلة 3508×2479.',
        'text_color'    => 'لون النص',
        'name_color'    => 'لون الاسم',
        'lines'         => 'محتوى الشهادة (حتى 5 أسطر)',
        'line'          => 'السطر',
        'insert'        => 'إدراج',
        'created_at'    => 'تاريخ الإنشاء',
        'creator'       => 'أنشأها',
        'empty'         => 'لا توجد قوالب تصميم بعد.',
        'preview_image' => 'معاينة الصورة',
        'no_background' => 'بدون خلفية',
        'dimension_warning' => 'تنبيه: الأبعاد المفضلة هي :w×:h بكسل، بينما الصورة المرفوعة :aw×:ah — تم الحفظ مع ذلك.',
        'types' => [
            'thanks'       => 'شكر',
            'appreciation' => 'شكر',
            'recognition'  => 'تقدير',
            'general'      => 'عام',
            'grades'       => 'إشعار درجات',
            'grades_notice'=> 'إشعار درجات',
        ],
        'orientations' => [
            'landscape' => 'أفقي',
            'portrait'  => 'رأسي',
        ],
        'placeholders' => [
            'student_name' => 'اسم الطالب',
            'school'       => 'المدرسة',
            'grade'        => 'الصف',
            'date'         => 'التاريخ',
        ],
        'flash' => [
            'created' => 'تم إضافة قالب التصميم بنجاح.',
            'updated' => 'تم تحديث قالب التصميم بنجاح.',
            'deleted' => 'تم حذف قالب التصميم بنجاح.',
        ],
    ],

    // Issue (template-based)
    'issue_page' => [
        'title'        => 'إصدار شهادات',
        'subtitle'     => 'اختر القالب والطلاب لإصدار الشهادات (فردي أو جماعي).',
        'select_template' => 'اختر التصميم',
        'select_students' => 'اختر الطلاب',
        'select_all'   => 'تحديد الكل',
        'no_templates' => 'لا توجد قوالب تصميم — أضف قالبًا أولًا.',
        'submit'       => 'إصدار الشهادات',
    ],

    // Preview / send
    'preview_page' => [
        'title'        => 'معاينة الشهادة',
        'student'      => 'اسم الطالب',
        'link'         => 'رابط الشهادة',
        'copy'         => 'نسخ الرابط',
        'view'         => 'عرض',
        'send'         => 'إرسال',
        'open_pdf'     => 'فتح ملف الـ PDF',
    ],
    'send_page' => [
        'title'        => 'إرسال الشهادة',
        'channels'     => 'قنوات الإرسال',
        'sms'          => 'رسائل نصية',
        'in_platform'  => 'داخل المنصة',
        'email'        => 'البريد الإلكتروني',
        'whatsapp'     => 'واتساب',
        'default_message' => 'عزيزي ولي أمر :student، يمكنكم الاطلاع على شهادة الطالب من خلال الرابط التالي: :link',
        'note'         => 'قنوات الإرسال تعتمد على وحدات المراسلة الحالية (رسائل/واتساب/بريد) وستُفعّل في بطاقة لاحقة.',
    ],

    // Buttons on index
    'templates_btn' => 'قوالب التصاميم',
    'issue_btn'     => 'إصدار شهادة +',
    'refresh'       => 'تحديث',

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
