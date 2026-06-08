<?php

return [
    'types' => [
        'rubric'       => 'Rubric',
        'rating_scale' => 'Rating Scale',
        'checklist'    => 'Checklist',
    ],
    'domains' => [
        'teacher'            => 'Teacher evaluation',
        'admin'              => 'Administrator evaluation',
        'class_visit'        => 'Class-visit evaluation',
        'student'            => 'Student evaluation',
        'parent'             => 'Parent evaluation',
        'school_environment' => 'School-environment evaluation',
        'general'            => 'General evaluation',
        'job_performance'    => 'Job-performance evaluation',
    ],
    'form_status' => [
        'draft'     => 'Draft',
        'ready'     => 'Ready to publish',
        'published' => 'Published',
        'closed'    => 'Closed',
        'archived'  => 'Archived',
    ],
    'eval_status' => [
        'draft'            => 'Draft',
        'completed'        => 'Completed',
        'pending_approval' => 'Pending approval',
        'approved'         => 'Approved',
        'rejected'         => 'Rejected',
        'needs_review'     => 'Needs review',
        'locked'           => 'Locked',
    ],
    'visit_status' => [
        'scheduled'        => 'Scheduled',
        'secret'           => 'Secret',
        'teacher_notified' => 'Teacher notified',
        'in_progress'      => 'In progress',
        'completed'        => 'Completed',
        'postponed'        => 'Postponed',
        'cancelled'        => 'Cancelled',
        'missed'           => 'Missed',
    ],
];
