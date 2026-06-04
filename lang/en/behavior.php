<?php

return [
    'search_btn' => 'Search',
    'yes' => 'Yes',
    'no' => 'No',
    'confirm_delete' => 'This group will be deleted. Are you sure?',

    'tabs' => [
        'students' => 'Students',
        'teachers' => 'Teachers',
    ],

    'types' => [
        'positive' => 'Positive',
        'negative' => 'Negative',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'disable' => 'Disable',
        'enable' => 'Enable',
    ],

    'groups' => [
        'title' => 'Behaviour Groups',
        'add' => 'Add behaviour group',
        'edit' => 'Edit behaviour group',
        'search' => 'Search by group name',
        'empty' => 'No behaviour groups in this tab.',
        'cols' => [
            'name' => 'Title',
            'type' => 'Type',
            'available_for_teacher' => 'Available to teacher',
            'behaviors' => 'Behaviours',
            'status' => 'Status',
            'created_at' => 'Created',
            'actions' => 'Actions',
        ],
        'fields' => [
            'name' => 'Group name',
            'type' => 'Group type',
            'available_for_teacher' => 'Allow teachers to use it',
            'is_active' => 'Active',
        ],
    ],

    'flash' => [
        'group_created' => 'Behaviour group added',
        'group_updated' => 'Behaviour group updated',
        'group_deleted' => 'Behaviour group deleted',
        'group_has_behaviors' => 'A group with linked behaviours cannot be deleted. You can disable it instead.',
    ],
];
