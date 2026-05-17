<?php

return [
    'page_title'   => 'Subject tracks',
    'plural'       => 'Subject tracks',
    'singular'     => 'Track',
    'help'         => 'A track groups subjects into an academic path such as General, Scientific, Literary, Memorisation, or International. The list shows up when creating or editing a subject.',

    'add'          => 'Add track',
    'edit'         => 'Edit track',
    'delete'       => 'Delete',
    'cancel'       => 'Cancel',
    'save'         => 'Save',
    'back'         => 'Back',
    'search_placeholder' => 'Search tracks…',

    'create_title' => 'Add a new track',
    'edit_title'   => 'Edit track',

    'columns' => [
        'name'       => 'Title',
        'name_en'    => 'English name',
        'sort_order' => 'Sort order',
        'is_active'  => 'Status',
        'actions'    => 'Actions',
    ],

    'form' => [
        'name'       => 'Track name (Arabic)',
        'name_en'    => 'Track name (English)',
        'sort_order' => 'Sort order',
        'is_active'  => 'Active',
        'notes'      => 'Optional notes',
        'is_active_hint' => 'Disabling hides the track from the picker without deleting it.',
    ],

    'status' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],

    'flash' => [
        'created' => 'Track added successfully',
        'updated' => 'Track updated',
        'deleted' => 'Track deleted',
    ],

    'empty' => [
        'title'     => 'No records found',
        'subtitle'  => 'No subject tracks yet. Click "Add track" to start.',
    ],

    'confirm_delete' => 'Delete this track?',
];
