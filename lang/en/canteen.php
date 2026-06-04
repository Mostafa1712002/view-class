<?php

return [
    'title' => 'Canteens',
    'add' => 'Create canteen',
    'edit' => 'Edit canteen',
    'search' => 'Search by canteen name',
    'search_btn' => 'Search',
    'empty' => 'No canteens yet.',
    'confirm_delete' => 'This canteen will be deleted. Are you sure?',
    'choose_school' => 'Choose a school',
    'no_manager' => 'No manager',
    'no_admins' => 'No admins available for this school.',
    'grade' => 'Grade :n',

    'cols' => [
        'name' => 'Canteen',
        'school' => 'School',
        'manager' => 'Manager',
        'readiness' => 'Readiness',
        'categories' => 'Categories',
        'products' => 'Products',
        'created_at' => 'Created',
        'status' => 'Status',
        'controls' => 'Actions',
    ],

    'fields' => [
        'name_ar' => 'Canteen name (Arabic)',
        'name_en' => 'Canteen name (English)',
        'school' => 'School',
        'target_grades' => 'Target grades',
        'target_grades_hint' => 'Leave all unchecked to allow every grade.',
        'manager' => 'Canteen manager',
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
        'assign_manager' => 'Assign manager',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
    ],

    'activation' => [
        'hint' => 'A canteen is created inactive and can only be activated after a manager is assigned and at least one category and one product are added.',
        'need_manager' => 'assign a canteen manager',
        'need_category' => 'add at least one active category',
        'need_product' => 'add at least one active product',
    ],

    'flash' => [
        'created' => 'Canteen created (inactive). Finish setup, then activate it.',
        'updated' => 'Canteen updated',
        'deleted' => 'Canteen deleted',
        'manager_assigned' => 'Canteen manager updated',
        'activated' => 'Canteen activated',
        'deactivated' => 'Canteen deactivated',
        'cannot_activate' => 'Cannot activate the canteen. Required: :reasons',
    ],
];
