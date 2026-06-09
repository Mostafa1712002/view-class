<?php

return [
    // ---- Approval queue (Task 14) -------------------------------------------
    'page_title'      => 'Evaluation Approvals',
    'breadcrumb'      => 'Approval & Review',
    'queue_title'     => 'Approval queue',
    'empty_title'     => 'No evaluations awaiting approval',
    'empty_subtitle'  => 'Completed, submitted-for-approval, or under-review evaluations will appear here.',

    'kpis' => [
        'pending'      => 'Pending approval',
        'completed'    => 'Completed',
        'needs_review' => 'Needs review',
        'approved'     => 'Approved',
    ],

    'filters' => [
        'status'  => 'Status',
        'form'    => 'Form',
        'all'     => 'All',
        'show'    => 'Filter',
        'reset'   => 'Reset',
    ],

    'columns' => [
        'id'         => 'ID',
        'form'       => 'Form',
        'subject'    => 'Subject',
        'evaluator'  => 'Evaluator',
        'score'      => 'Score',
        'status'     => 'Status',
        'submitted'  => 'Submitted',
        'actions'    => 'Actions',
    ],

    'actions' => [
        'view'    => 'View',
        'approve' => 'Approve',
        'reject'  => 'Reject',
        'review'  => 'Request review',
        'reopen'  => 'Reopen',
        'back'    => 'Back',
        'cancel'  => 'Cancel',
    ],

    // ---- Detail / show -------------------------------------------------------
    'detail' => [
        'title'        => 'Evaluation details',
        'info'         => 'Evaluation info',
        'result'       => 'Result',
        'total'        => 'Total',
        'max'          => 'Max',
        'percentage'   => 'Percentage',
        'grade'        => 'Grade',
        'breakdown'    => 'Score breakdown',
        'item'         => 'Item',
        'earned'       => 'Earned',
        'answers'      => 'Answers',
        'evidence'     => 'Evidence',
        'evidence_count' => 'Evidence count',
        'no_evidence'  => 'No evidence',
        'general_notes'=> 'General notes',
        'rejection_reason' => 'Rejection reason',
        'review_notes' => 'Review notes',
        'met'          => 'Met',
        'not_met'      => 'Not met',
        'none'         => '—',
    ],

    // ---- Action modals -------------------------------------------------------
    'approve_confirm' => 'Approve this evaluation? It will be locked after approval.',
    'reject_title'    => 'Reject evaluation',
    'reject_reason'   => 'Rejection reason',
    'reject_reason_ph'=> 'Explain why so the evaluator can address it…',
    'review_title'    => 'Request review',
    'review_notes'    => 'Review notes',
    'review_notes_ph' => 'Write notes and list the items that need review…',
    'reopen_confirm'  => 'Reopening returns this evaluation to draft so the evaluator can edit it. Continue?',

    // ---- Flash --------------------------------------------------------------
    'flash' => [
        'approved' => 'Evaluation approved successfully.',
        'rejected' => 'Evaluation rejected and the evaluator notified.',
        'reviewed' => 'Evaluation sent for review and the evaluator notified.',
        'reopened' => 'Evaluation reopened.',
    ],

    // ---- Errors / guards ----------------------------------------------------
    'errors' => [
        'cannot_approve'    => 'This evaluation cannot be approved in its current state.',
        'cannot_reject'     => 'This evaluation cannot be rejected in its current state.',
        'cannot_review'     => 'This evaluation cannot be sent for review in its current state.',
        'cannot_reopen'     => 'This evaluation cannot be reopened in its current state.',
        'reopen_forbidden'  => 'You do not have permission to reopen evaluations.',
        'reason_required'   => 'A rejection reason is required.',
        'notes_required'    => 'Review notes are required.',
        'evidence_missing'  => 'Cannot approve: required evidence is missing for ":node".',
    ],

    // ---- Notifications (review / reopen use generic notify) ------------------
    'notify' => [
        'review_title'  => 'Evaluation needs review',
        'review_body'   => 'One of your evaluations was sent for review. Please check the notes.',
        'reopen_title'  => 'Evaluation reopened',
        'reopen_body'   => 'One of your evaluations was reopened and is now editable.',
    ],

    // ---- Job-performance linkage view (Task 15) -----------------------------
    'jp' => [
        'page_title'    => 'Job-performance linkage',
        'breadcrumb'    => 'Job performance',
        'detail_title'  => 'Teacher performance details',
        'empty_title'   => 'No job-performance-linked results',
        'empty_subtitle'=> 'Results from forms with job-performance linkage enabled appear here.',
        'readonly_note' => 'Read-only view; linkage settings are configured on the form edit screen.',

        'kpis' => [
            'teachers'     => 'Teachers',
            'evaluations'  => 'Linked evaluations',
            'avg'          => 'Overall average',
            'forms'        => 'Linked forms',
        ],

        'columns' => [
            'teacher'     => 'Teacher',
            'school'      => 'School',
            'count'       => 'Evaluations',
            'average'     => 'Average',
            'latest'      => 'Latest',
            'effective'   => 'Effective value',
            'status_mix'  => 'Statuses',
            'actions'     => 'Actions',
        ],

        'detail' => [
            'form'        => 'Form',
            'evaluator'   => 'Evaluator',
            'party'       => 'Party',
            'score'       => 'Score',
            'percentage'  => 'Percentage',
            'date'        => 'Date',
            'status'      => 'Status',
            'evidence'    => 'Evidence',
            'weight'      => 'Weight',
            'count_on'    => 'Counts on',
            'aggregation' => 'Aggregation',
        ],

        'aggregation' => [
            'last'    => 'Latest',
            'average' => 'Average',
        ],
        'count_on' => [
            'submit'  => 'Submission',
            'approve' => 'Approval',
        ],

        'view'        => 'View',
        'no_settings' => 'Default (average of completed + approved)',
    ],
];
