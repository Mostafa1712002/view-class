<?php

return [
    // Page titles
    'title'              => 'Certificates',
    'issue'              => 'Issue Certificate',
    'admin_title'        => 'Manage Certificates',
    'my_title'           => 'My Certificates',
    'breadcrumb_home'    => 'Home',
    'breadcrumb_index'   => 'Certificates',
    'breadcrumb_create'  => 'Issue New Certificate',
    'breadcrumb_edit'    => 'Edit Certificate',

    // Types
    'types' => [
        'student'       => 'Student',
        'teacher'       => 'Teacher',
        'training'      => 'Training',
        'appreciation'  => 'Appreciation',
    ],

    // Statuses
    'status' => [
        'draft'     => 'Draft',
        'published' => 'Published',
    ],

    // Fields
    'fields' => [
        'type'             => 'Certificate Type',
        'title'            => 'Certificate Title',
        'recipient'        => 'Recipient',
        'issued_by'        => 'Issued By',
        'issue_date'       => 'Issue Date',
        'status'           => 'Status',
        'note'             => 'Notes',
        'file'             => 'Certificate File (PDF or image)',
        'actions'          => 'Actions',
        'school'           => 'School',
        'grade'            => 'Grade',
        'template'         => 'Template',
        'progress'         => 'Progress',
        'students'         => 'Students',
    ],

    // Actions
    'actions' => [
        'publish'   => 'Publish',
        'edit'      => 'Edit',
        'delete'    => 'Delete',
        'download'  => 'Download',
        'create'    => 'Issue Certificate',
        'save'      => 'Save',
        'cancel'    => 'Cancel',
    ],

    // Filters
    'filter_type'   => 'Certificate Type',
    'filter_q'      => 'Search (title / recipient)',
    'filter_all'    => 'All',
    'filter_apply'  => 'Search',
    'filter_reset'  => 'Reset',

    // Flash messages
    'flash' => [
        'created'   => 'Certificate issued successfully.',
        'updated'   => 'Certificate updated successfully.',
        'published' => 'Certificate published successfully.',
        'deleted'   => 'Certificate deleted successfully.',
        'issued'    => ':count certificate(s) issued successfully.',
    ],

    // Design templates
    'tpl' => [
        'index_title'   => 'Design Templates',
        'create_title'  => 'Add Design Template',
        'edit_title'    => 'Edit Design Template',
        'back'          => 'Back',
        'add'           => 'Add +',
        'name'          => 'Template Name',
        'type'          => 'Design / Type',
        'orientation'   => 'Layout',
        'background'    => 'Background',
        'background_hint' => 'Formats: jpg, jpeg, png, webp — under 1.5 MB — preferred size 3508×2479.',
        'text_color'    => 'Text Color',
        'name_color'    => 'Name Color',
        'lines'         => 'Certificate Content (up to 5 lines)',
        'line'          => 'Line',
        'insert'        => 'Insert',
        'created_at'    => 'Created At',
        'creator'       => 'Created By',
        'empty'         => 'No design templates yet.',
        'preview_image' => 'Image Preview',
        'no_background' => 'No background',
        'dimension_warning' => 'Notice: preferred size is :w×:h px, but the uploaded image is :aw×:ah — saved anyway.',
        'types' => [
            'appreciation' => 'Appreciation',
            'recognition'  => 'Recognition',
            'general'      => 'General',
            'grades_notice'=> 'Grades Notice',
        ],
        'orientations' => [
            'landscape' => 'Landscape',
            'portrait'  => 'Portrait',
        ],
        'placeholders' => [
            'student_name' => 'Student Name',
            'school'       => 'School',
            'grade'        => 'Grade',
            'date'         => 'Date',
        ],
        'flash' => [
            'created' => 'Design template added successfully.',
            'updated' => 'Design template updated successfully.',
            'deleted' => 'Design template deleted successfully.',
        ],
    ],

    // Issue (template-based)
    'issue_page' => [
        'title'        => 'Issue Certificates',
        'subtitle'     => 'Pick a template and students to issue certificates (single or bulk).',
        'select_template' => 'Select template',
        'select_students' => 'Select students',
        'select_all'   => 'Select all',
        'no_templates' => 'No design templates — add one first.',
        'submit'       => 'Issue Certificates',
    ],

    // Preview / send
    'preview_page' => [
        'title'        => 'Certificate Preview',
        'student'      => 'Student Name',
        'link'         => 'Certificate Link',
        'copy'         => 'Copy Link',
        'view'         => 'View',
        'send'         => 'Send',
        'open_pdf'     => 'Open PDF',
    ],
    'send_page' => [
        'title'        => 'Send Certificate',
        'channels'     => 'Send Channels',
        'sms'          => 'SMS',
        'in_platform'  => 'In-platform',
        'email'        => 'Email',
        'whatsapp'     => 'WhatsApp',
        'default_message' => 'Dear parent of :student, you can view the student certificate via: :link',
        'note'         => 'Send channels rely on existing messaging modules (SMS/WhatsApp/Email) and will be wired in a later card.',
    ],

    // Buttons on index
    'templates_btn' => 'Design Templates',
    'issue_btn'     => 'Issue Certificate +',
    'refresh'       => 'Refresh',

    // Empty states
    'empty'    => 'No certificates found.',
    'empty_my' => 'No certificates linked to your account.',

    // Confirm messages
    'confirm_publish' => 'Are you sure you want to publish this certificate?',
    'confirm_delete'  => 'Are you sure you want to delete this certificate?',

    // Choose placeholder
    'choose_type'      => 'Select certificate type',
    'choose_status'    => 'Select status',
    'choose_recipient' => 'Select recipient',
];
