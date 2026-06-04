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

    'point_types' => [
        'add' => 'Add',
        'deduct' => 'Deduct',
    ],

    'behaviors' => [
        'title' => 'Behaviours',
        'add' => 'Add behaviour',
        'edit' => 'Edit behaviour',
        'search' => 'Search by behaviour name',
        'empty' => 'No behaviours in this tab.',
        'choose_group' => 'Choose a group',
        'no_groups' => 'No active groups in this tab. Add a group first.',
        'cols' => [
            'name' => 'Behaviour',
            'group' => 'Group',
            'group_type' => 'Group type',
            'actions_count' => 'Actions',
            'status' => 'Status',
            'created_at' => 'Created',
            'controls' => 'Actions',
        ],
        'fields' => [
            'group' => 'Group',
            'name' => 'Behaviour name',
            'description' => 'Behaviour description',
            'is_active' => 'Active',
        ],
    ],

    'actions_page' => [
        'title' => 'Actions',
        'add' => 'Add action',
        'edit' => 'Edit action',
        'search' => 'Search in action description',
        'empty' => 'No actions in this tab.',
        'choose_behavior' => 'Choose a behaviour',
        'no_behaviors' => 'No active behaviours in this tab. Add a behaviour first.',
        'cols' => [
            'title' => 'Action',
            'behavior' => 'Behaviour',
            'points' => 'Points',
            'effect' => 'Effect',
            'notify' => 'Notify parent',
            'followup' => 'Needs follow-up',
            'status' => 'Status',
            'controls' => 'Actions',
        ],
        'fields' => [
            'behavior' => 'Behaviour',
            'description' => 'Action description',
            'points' => 'Points',
            'point_type' => 'Point type',
            'notify_parent' => 'Notify parent',
            'needs_followup' => 'Needs follow-up',
            'is_active' => 'Active',
        ],
    ],

    'flash' => [
        'group_created' => 'Behaviour group added',
        'group_updated' => 'Behaviour group updated',
        'group_deleted' => 'Behaviour group deleted',
        'group_has_behaviors' => 'A group with linked behaviours cannot be deleted. You can disable it instead.',
        'behavior_created' => 'Behaviour added',
        'behavior_updated' => 'Behaviour updated',
        'behavior_deleted' => 'Behaviour deleted',
        'behavior_has_actions' => 'A behaviour with linked actions cannot be deleted. You can disable it instead.',
        'action_created' => 'Action added',
        'action_updated' => 'Action updated',
        'action_deleted' => 'Action deleted',
    ],
];
