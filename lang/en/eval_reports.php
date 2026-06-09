<?php

return [
    // Page titles
    'supervisors_title'          => 'Supervisors Report (Summary)',
    'supervisors_detailed_title' => 'Supervisors Detailed Report',
    'general_manager_title'      => 'General Manager Screen',

    'breadcrumb_reports'         => 'Evaluation Reports',

    // Buttons
    'print'        => 'Print',
    'export_csv'   => 'Export CSV',
    'show'         => 'Show',
    'reset'        => 'Reset',
    'view'         => 'View evaluation',
    'all'          => 'All',

    // Filters
    'filters' => [
        'form'           => 'Form',
        'company'        => 'Company',
        'complex'        => 'Complex',
        'school'         => 'School',
        'stage'          => 'Stage',
        'section'        => 'Section',
        'grade'          => 'Grade',
        'subject'        => 'Subject',
        'specialization' => 'Specialization',
        'supervisor'     => 'Supervisor',
        'teacher'        => 'Teacher',
        'evaluator'      => 'Evaluator',
        'party'          => 'Evaluation party',
        'role'           => 'Role',
        'eval_status'    => 'Evaluation status',
        'visit_status'   => 'Visit status',
        'period'         => 'Period',
        'date_from'      => 'Date from',
        'date_to'        => 'Date to',
        'score_from'     => 'Score from',
        'score_to'       => 'Score to',
        'has_evidence'   => 'Has evidence?',
        'has_missing'    => 'Has missing items?',
        'yes'            => 'Yes',
        'no'             => 'No',
    ],

    // KPI tiles — supervisor summary
    'kpis' => [
        'supervisors'      => 'Supervisors',
        'total_visits'     => 'Total visits',
        'total_evals'      => 'Total evaluations',
        'completed'        => 'Completed',
        'incomplete'       => 'Incomplete',
        'postponed_visits' => 'Postponed visits',
        'cancelled_visits' => 'Cancelled visits',
        'avg_pct'          => 'Avg evaluation %',
        'top_supervisor'   => 'Top supervisor',
        'low_supervisor'   => 'Lowest supervisor',
        'completion_pct'   => 'Overall completion %',

        // GM
        'teachers'         => 'Teachers',
        'approved'         => 'Approved',
        'pending_approval' => 'Pending approval',
        'highest'          => 'Highest performance',
        'lowest'           => 'Lowest performance',
        'avg_performance'  => 'Avg performance',
        'without_evidence' => 'Evals without evidence',
        'needs_review'     => 'Needs review',
    ],

    // Table columns
    'cols' => [
        'supervisor'     => 'Supervisor',
        'scheduled'      => 'Scheduled visits',
        'executed'       => 'Executed visits',
        'not_executed'   => 'Not executed',
        'evaluations'    => 'Evaluations',
        'completed'      => 'Completed',
        'incomplete'     => 'Incomplete',
        'avg_pct'        => 'Avg %',
        'completion_pct' => 'Completion %',
        'last_visit'     => 'Last visit',

        'form'           => 'Form',
        'teacher'        => 'Teacher',
        'school'         => 'School',
        'complex'        => 'Complex',
        'stage'          => 'Stage',
        'subject'        => 'Subject',
        'specialization' => 'Specialization',
        'form_type'      => 'Form type',
        'visit_type'     => 'Visit type',
        'visit_date'     => 'Visit date',
        'eval_date'      => 'Eval date',
        'total_score'    => 'Score',
        'percentage'     => '%',
        'final_score'    => 'Final score',
        'status'         => 'Status',
        'teacher_viewed' => 'Teacher viewed?',
        'teacher_commented' => 'Teacher commented?',
        'evidence'       => 'Evidence',
        'notes'          => 'Notes',
        'items'          => 'Items',
        'party'          => 'Party',
        'evaluator'      => 'Evaluator',
        'dept'           => 'Department',
        'last_update'    => 'Last update',
        'actions'        => 'Actions',
    ],

    'multiple_evaluators' => 'Multiple evaluators',
    'na'                  => '—',

    // Empty states
    'empty' => [
        'title'    => 'No data',
        'subtitle' => 'No evaluations or visits match the selected filters.',
    ],
];
