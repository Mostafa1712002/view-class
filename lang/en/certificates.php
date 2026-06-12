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
    ],

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
