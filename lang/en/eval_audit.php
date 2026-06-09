<?php

return [
    'title'             => 'Audit Log',
    'breadcrumb_eval'   => 'Evaluation',
    'subtitle'          => 'Evaluation module audit trail (publish, approve, reject, execute, evidence...)',

    'filters' => [
        'user'      => 'User',
        'action'    => 'Action type',
        'date_from' => 'From date',
        'date_to'   => 'To date',
        'search'    => 'Search description',
        'search_ph' => 'Search description or affected model...',
    ],

    'cols' => [
        'user'        => 'User',
        'role'        => 'Role',
        'datetime'    => 'Date & time',
        'action'      => 'Action',
        'model'       => 'Affected model',
        'description' => 'Description',
        'ip'          => 'IP',
    ],

    'all'    => 'All',
    'show'   => 'Show',
    'reset'  => 'Reset',
    'system' => 'System',
    'no_role' => '—',
    'empty'  => 'No matching operations.',
    'count'  => ':count operations',
];
