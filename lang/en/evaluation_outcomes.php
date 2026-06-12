<?php

/**
 * Phase C (#205) — English translations for educational outcomes module.
 */
return [

    // Page titles
    'page_title'        => 'Educational Outcomes',
    'create_title'      => 'Add Educational Outcome',
    'show_title'        => 'Outcome Details',
    'settings_title'    => 'Outcome Averaging Method Settings',

    // Breadcrumb
    'breadcrumb_index'    => 'Educational Outcomes',
    'breadcrumb_create'   => 'Add',
    'breadcrumb_show'     => 'Details',
    'breadcrumb_settings' => 'Calculation Settings',

    // Averaging methods
    'methods' => [
        'all_registered'  => 'All Registered (absent = 0)',
        'attendees_only'  => 'Attendees Only',
        'label'           => 'Averaging Method',
    ],

    // Source labels
    'sources' => [
        'manual'           => 'Manual Entry',
        'imported'         => 'Imported from File',
        'internal'         => 'Internal Platform',
        'external_alawwal' => 'Al-Awwal Platform',
        'external_qudrat'  => 'Ana wal-Qudrat Platform',
    ],

    // Approval status
    'approval_status' => [
        'draft'    => 'Draft',
        'approved' => 'Approved',
    ],

    // Table columns
    'columns' => [
        'test_name'      => 'Test Name',
        'test_date'      => 'Test Date',
        'grade_level'    => 'Grade',
        'class_label'    => 'Class',
        'method_used'    => 'Averaging Method',
        'final_average'  => 'Final Average',
        'registered'     => 'Registered',
        'present'        => 'Present',
        'absent'         => 'Absent',
        'source'         => 'Source',
        'status'         => 'Status',
        'actions'        => 'Actions',
        'created_at'     => 'Created At',
    ],

    // Form labels
    'fields' => [
        'test_name'              => 'Test Name',
        'test_type'              => 'Test Type',
        'test_date'              => 'Test Date',
        'grade_level'            => 'Grade Level',
        'class_label'            => 'Class',
        'teacher_id'             => 'Teacher',
        'subject_id'             => 'Subject',
        'educational_company_id' => 'Content Company',
        'source'                 => 'Data Source',
        'method'                 => 'Averaging Method',
        'students'               => 'Student Data',
        'reason'                 => 'Reason for Recomputation',
    ],

    // Settings page
    'settings' => [
        'school_method_label'  => 'Averaging method for this school',
        'global_method_label'  => 'System-wide default (super-admin)',
        'effective_label'      => 'Currently effective method',
        'school_override_note' => 'A school-level setting overrides the global default.',
        'method_warning'       => 'Changing the method will not affect already-computed outcomes unless recomputed.',
        'global_only_for_super'=> 'Changing the global default is restricted to super-admins.',
    ],

    // Detail view
    'detail' => [
        'recompute_heading' => 'Recompute',
        'students_heading'  => 'Student Data',
        'student_id'        => 'Student ID',
        'score'             => 'Score',
        'status'            => 'Status',
        'present'           => 'Present',
        'absent'            => 'Absent',
        'approved_locked'   => 'This outcome is approved — admin permission required to recompute.',
    ],

    // Flash messages
    'flash' => [
        'created'         => 'Educational outcome computed and saved successfully.',
        'recomputed'      => 'Outcome recomputed successfully.',
        'settings_saved'  => 'Calculation settings saved.',
        'select_school_first' => 'Please select a school before adding an outcome.',
    ],

    // Validation messages
    'validation' => [
        'test_name_required'  => 'Test name is required.',
        'students_required'   => 'Student data is required.',
        'students_min'        => 'At least one student record is required.',
        'student_id_required' => 'Student ID is required.',
        'score_numeric'       => 'Score must be a number.',
        'score_min'           => 'Score cannot be less than 0.',
        'score_max'           => 'Score cannot exceed 100.',
        'status_invalid'      => 'Student status must be "present" or "absent".',
    ],

    // Errors
    'errors' => [
        'approved_locked' => 'Cannot recompute an approved outcome without admin privileges.',
        'not_found'       => 'Educational outcome not found.',
        'access_denied'   => 'You do not have access to this outcome.',
    ],

    // Buttons / actions
    'actions' => [
        'add'          => 'Add Educational Outcome',
        'recompute'    => 'Recompute',
        'settings'     => 'Calculation Settings',
        'back_to_list' => 'Back to List',
        'save'         => 'Compute & Save',
    ],

    // Empty state
    'empty' => [
        'heading'     => 'No educational outcomes yet',
        'description' => 'Start by adding an educational outcome to track class averages.',
    ],
];
