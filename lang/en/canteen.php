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

    'categories' => [
        'title' => 'Categories',
        'add' => 'Add category',
        'empty' => 'No categories yet.',
        'fields' => [
            'name' => 'Category name',
            'sort_order' => 'Display order',
        ],
        'flash' => [
            'created' => 'Category added',
            'updated' => 'Category updated',
            'deleted' => 'Category deleted',
            'has_products' => 'A category with products cannot be deleted. Remove or disable its products first.',
        ],
    ],

    'products' => [
        'title' => 'Products',
        'add' => 'Add product',
        'edit' => 'Edit product',
        'empty' => 'No products yet.',
        'choose_category' => 'Choose a category',
        'no_categories' => 'Add at least one active category before adding products.',
        'cols' => [
            'image' => 'Image',
            'name' => 'Product',
            'category' => 'Category',
            'price' => 'Price',
            'calories' => 'Calories',
        ],
        'fields' => [
            'category' => 'Category',
            'name' => 'Product name',
            'price' => 'Price',
            'calories' => 'Calories',
            'image' => 'Product image',
            'sort_order' => 'Display order',
            'is_active' => 'Active',
        ],
        'flash' => [
            'created' => 'Product added',
            'updated' => 'Product updated',
            'deleted' => 'Product deleted',
        ],
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
