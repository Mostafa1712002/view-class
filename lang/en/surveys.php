<?php

return [

    // ── Page titles ───────────────────────────────────────────────────────────
    'admin_title'    => 'Surveys',
    'my_title'       => 'My Surveys',
    'create_title'   => 'Create Survey',
    'edit_title'     => 'Edit Survey',
    'results_title'  => 'Survey Results',

    // ── Breadcrumbs ───────────────────────────────────────────────────────────
    'breadcrumb_home'    => 'Home',
    'breadcrumb_index'   => 'Surveys',
    'breadcrumb_create'  => 'Create',
    'breadcrumb_edit'    => 'Edit',
    'breadcrumb_results' => 'Results',
    'breadcrumb_my'      => 'My Surveys',

    // ── Actions ───────────────────────────────────────────────────────────────
    'actions' => [
        'create'  => 'Create Survey',
        'edit'    => 'Edit',
        'publish' => 'Publish',
        'close'   => 'Close',
        'results' => 'Results',
        'delete'  => 'Delete',
        'take'    => 'Take Survey',
        'submit'  => 'Submit Answers',
        'save'    => 'Save',
    ],

    // ── Statuses ──────────────────────────────────────────────────────────────
    'statuses' => [
        'draft'     => 'Draft',
        'published' => 'Published',
        'closed'    => 'Closed',
    ],

    // ── Audiences ─────────────────────────────────────────────────────────────
    'audiences' => [
        'all'      => 'Everyone',
        'students' => 'Students',
        'parents'  => 'Parents',
        'teachers' => 'Teachers',
    ],

    // ── Question types ────────────────────────────────────────────────────────
    'question_types' => [
        'single_choice'   => 'Single Choice',
        'multiple_choice' => 'Multiple Choice',
        'text'            => 'Text Answer',
        'rating'          => 'Rating (1-5)',
    ],

    // ── Fields ────────────────────────────────────────────────────────────────
    'fields' => [
        'title'           => 'Title',
        'description'     => 'Description',
        'status'          => 'Status',
        'audience'        => 'Target Audience',
        'starts_at'       => 'Start Date',
        'ends_at'         => 'End Date',
        'responses'       => 'Responses',
        'created_by'      => 'Created By',
        'questions'       => 'Questions',
        'question_text'   => 'Question Text',
        'question_type'   => 'Question Type',
        'required'        => 'Required',
        'options'         => 'Options',
        'actions'         => 'Actions',
    ],

    // ── Flash messages ────────────────────────────────────────────────────────
    'flash' => [
        'created'   => 'Survey created successfully.',
        'updated'   => 'Survey updated successfully.',
        'published' => 'Survey published successfully.',
        'closed'    => 'Survey closed.',
        'deleted'   => 'Survey deleted.',
        'submitted' => 'Thank you! Your answers have been submitted.',
    ],

    // ── Confirm dialogs ───────────────────────────────────────────────────────
    'confirm' => [
        'publish' => 'Are you sure you want to publish this survey?',
        'close'   => 'Are you sure you want to close this survey?',
        'delete'  => 'Are you sure you want to delete this survey? This cannot be undone.',
    ],

    // ── Empty / misc ──────────────────────────────────────────────────────────
    'empty'              => 'No surveys yet.',
    'empty_pending'      => 'No surveys available for you at the moment.',
    'empty_answered'     => 'You have not answered any surveys yet.',
    'results_empty'      => 'No responses yet.',
    'responses_count'    => 'Responses',
    'add_question'       => '+ Add Question',
    'add_option'         => '+ Add Option',
    'remove_question'    => 'Remove Question',
    'remove_option'      => 'Remove',
    'pending_surveys'    => 'Available Surveys',
    'answered_surveys'   => 'Completed Surveys',
    'answered_badge'     => 'Answered',
    'section_text_answers'   => 'Text Answers',
    'section_choice_counts'  => 'Answer Distribution',
    'no_text_answers'    => 'No text answers.',
    'rating_label'       => 'Rating: :value',

    // ── Validation ────────────────────────────────────────────────────────────
    'validation' => [
        'required_question' => 'This question is required.',
    ],
];
