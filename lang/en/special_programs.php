<?php

return [
    'title' => 'Special Educational Programs',
    'programs' => ['qiyas' => 'Qiyas (Aptitude)', 'tahsili' => 'Tahsili (Achievement)', 'nafis' => 'Nafis'],
    'subjects' => [
        'qiyas_verbal' => 'Qiyas Verbal', 'qiyas_quant' => 'Qiyas Quantitative',
        'tahsili_physics' => 'Tahsili Physics', 'tahsili_chemistry' => 'Tahsili Chemistry',
        'tahsili_biology' => 'Tahsili Biology', 'tahsili_math' => 'Tahsili Math',
        'nafis_reading' => 'Nafis Reading', 'nafis_math' => 'Nafis Math', 'nafis_science' => 'Nafis Science',
    ],
    'levels' => [
        'excellent' => 'Excellent', 'very_good' => 'Very good', 'good' => 'Good', 'acceptable' => 'Acceptable', 'weak' => 'Weak',
    ],
    'attempt_status' => [
        'in_progress' => 'In progress', 'completed' => 'Completed', 'timed_out' => 'Timed out', 'auto_ended' => 'Auto-ended',
    ],
    'exam_types' => ['single' => 'Single-subject exam', 'multi' => 'Multi-subject exam'],
    'creator_roles' => ['admin' => 'Administration', 'teacher' => 'Teacher'],
    'difficulty' => ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'],
    'terms' => ['first' => 'First term', 'second' => 'Second term', 'both' => 'Both terms'],
    'buttons' => [
        'save' => 'Save', 'cancel' => 'Cancel', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete',
        'back' => 'Back', 'search' => 'Search', 'export_excel' => 'Export Excel', 'print' => 'Print', 'start' => 'Start',
        'finish' => 'Finish', 'view' => 'View',
    ],
    'not_enrolled' => 'You are not enrolled in any program',
    'no_programs_assigned' => 'You have not been assigned to any program',
    'empty_bank' => 'The question bank for this program has not been imported yet',
];
