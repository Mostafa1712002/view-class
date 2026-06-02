<?php

return [
    'title' => 'Education Policies',
    'add' => 'Add Policy',
    'edit' => 'Edit Policy',
    'search' => 'Search by policy title',
    'my_title' => 'Education Policies',
    'my_intro' => 'Organisational policies and instructions for you. Click a policy to read it.',

    'cols' => [
        'title' => 'Title',
        'description' => 'Description',
        'beneficiaries' => 'Beneficiaries',
        'read' => 'Read',
        'roles' => 'Target roles',
        'created_at' => 'Added',
        'creator' => 'Creator',
        'actions' => 'Actions',
        'status' => 'Status',
        'read_at' => 'Read at',
        'user' => 'User',
        'role' => 'Role',
    ],

    'fields' => [
        'title' => 'Policy name',
        'description' => 'Policy description',
        'target_roles' => 'Target roles',
        'file' => 'Upload file',
        'external_url' => 'External URL',
    ],

    'roles' => [
        'student' => 'Students',
        'teacher' => 'Teachers',
        'parent' => 'Parents',
        'school-admin' => 'Admins',
    ],

    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view_acks' => 'Acknowledgements',
        'open' => 'Open file',
        'open_link' => 'Open link',
        'read' => 'Read',
        'back' => 'Back',
    ],

    'status' => [
        'read' => 'Read',
        'unread' => 'Not read',
    ],

    'empty' => 'No policies.',
    'my_empty' => 'You have no policies right now.',
    'no_attachment' => 'No attachment for this policy.',
    'confirm_delete' => 'The policy will be deleted and hidden from users. Are you sure?',

    'notify' => [
        'title' => 'New education policy',
        'action' => 'View policy',
    ],

    'flash' => [
        'created' => 'Policy added and target roles notified',
        'updated' => 'Policy updated',
        'deleted' => 'Policy deleted',
    ],

    'acks_title' => 'Policy acknowledgements',
];
