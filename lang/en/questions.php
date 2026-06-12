<?php

return [
    'page_title' => 'Question bank questions',
    'index_title' => 'Questions',
    'create_title' => 'Add a new question',
    'edit_title' => 'Edit question',
    'preview_title' => 'Preview question',
    'add_btn' => 'Add question',
    'duplicate_btn' => 'Duplicate',
    'copy_suffix' => '(copy)',

    'breadcrumb' => [
        'home' => 'Home',
        'banks' => 'Question banks',
        'questions' => 'Questions',
    ],

    'types' => [
        'mcq' => 'Multiple choice',
        'true_false' => 'True / false',
        'essay' => 'Essay',
        'matching' => 'Matching',
        'fill_blank' => 'Fill in the blank',
        'short' => 'Short answer',
    ],

    'difficulty' => [
        '1' => 'Easy',
        '2' => 'Medium',
        '3' => 'Hard',
    ],

    'status' => [
        'draft' => 'Draft',
        'pending_review' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
        // legacy fallback
        'published' => 'Approved',
    ],

    'columns' => [
        'lesson' => 'Lesson',
        'creator' => 'Created by',
        'type' => 'Type',
        'body' => 'Question text',
        'points' => 'Points',
        'difficulty' => 'Difficulty',
        'status' => 'Status',
        'created_at' => 'Created at',
        'standard' => 'Has standard',
        'actions' => 'Actions',
        'code' => 'Code',
        'content_type' => 'Content type',
    ],

    'filters' => [
        'title' => 'Filters',
        'reset' => 'Reset',
        'search' => 'Search inside question text',
        'type' => 'Question type',
        'difficulty' => 'Difficulty',
        'lesson' => 'Lesson',
        'status' => 'Status',
        'all' => 'All',
        'code' => 'Question code',
        'content_type' => 'Content type',
        'source' => 'Source',
        'has_image' => 'Has image',
        'full_image_only' => 'Full-image only',
    ],

    'content_type' => [
        'text'  => 'Text',
        'image' => 'Image',
        'mixed' => 'Mixed',
    ],

    'errors' => [
        'code_required_image' => 'Please enter a question code so it can be found when an error is reported.',
        'code_duplicate'      => 'This question code is already used in this bank — please enter a different one.',
        'image_required'      => 'A full-image question must have an image uploaded.',
    ],

    'form' => [
        'sections' => [
            'info' => 'Question info',
            'answer' => 'Answer info',
        ],
        'type' => 'Question type',
        'difficulty' => 'Difficulty',
        'points' => 'Points',
        'lesson' => 'Linked lesson',
        'lesson_placeholder' => 'None',
        'status' => 'Status',
        'attachment' => 'Attach a file with the question',
        'attachment_help' => 'Image / PDF / audio / video — up to 10 MB',
        'remove_attachment' => 'Remove the current attachment',
        'body_ar' => 'Question text (Arabic)',
        'body_ar_help' => 'Not required if the question is a full image',
        'body_en' => 'Question text (English — optional)',
        'code' => 'Question code',
        'code_placeholder' => 'e.g. Q-1024',
        'code_help' => 'Optional for text questions; required for a full-image question. Unique within the bank.',
        'content_type' => 'Content type',
        'is_full_image' => 'Full-image question (no text head)',

        'mcq' => [
            'add_option' => 'Add option',
            'option_n' => 'Option',
            'correct' => 'Correct',
            'remove' => 'Remove',
        ],
        'true_false' => [
            'correct' => 'Correct answer',
            'true' => 'True',
            'false' => 'False',
        ],
        'essay' => [
            'model_answer' => 'Model answer (guideline)',
        ],
        'short' => [
            'model_answer' => 'Model answer',
        ],
        'matching' => [
            'add_pair' => 'Add pair',
            'left' => 'Left column',
            'right' => 'Right column',
            'remove' => 'Remove',
        ],
        'fill_blank' => [
            'add_blank' => 'Add blank',
            'blank_n' => 'Blank answer',
            'remove' => 'Remove',
            'hint' => 'Write the prompt above and use ____ where blanks should appear',
        ],

        'save' => 'Save',
        'reset' => 'Clear',
        'back' => 'Back',
        'cancel' => 'Cancel',
    ],

    'preview' => [
        'difficulty' => 'Difficulty',
        'points' => 'Points',
        'lesson' => 'Lesson',
        'type' => 'Type',
        'body' => 'Question text',
        'answers' => 'Answers',
        'correct' => 'Correct answer',
        'model_answer' => 'Model answer',
        'attachment' => 'Attachment',
        'no_attachment' => 'No attachment',
        'open_attachment' => 'Open attachment',
        'close' => 'Close',
        'code' => 'Question code',
    ],

    'has_image' => 'Has image',
    'no_image' => 'No image',

    'flash' => [
        'created' => 'Question added',
        'updated' => 'Question updated',
        'deleted' => 'Question deleted',
        'duplicated' => 'Question duplicated — you can edit the new copy now',
    ],

    'confirm' => [
        'delete' => 'Delete this question?',
    ],

    'empty' => 'No questions in this bank yet.',
    'has_standard_yes' => 'Yes',
    'has_standard_no' => 'No',
    'view_actions' => [
        'edit' => 'Edit',
        'preview' => 'Preview',
        'duplicate' => 'Duplicate',
        'delete' => 'Delete',
        'settings' => 'Settings',
    ],
];
