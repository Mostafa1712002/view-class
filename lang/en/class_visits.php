<?php

return [
    'page_title' => 'Class Visits',
    'add'        => 'Schedule Visit',

    'kpis' => [
        'total'       => 'Total Visits',
        'scheduled'   => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    ],

    'filters' => [
        'search'     => 'Search',
        'school'     => 'School',
        'stage'      => 'Stage',
        'grade'      => 'Grade',
        'section'    => 'Section',
        'teacher'    => 'Teacher',
        'subject'    => 'Subject',
        'supervisor' => 'Supervisor',
        'status'     => 'Visit Status',
        'date_from'  => 'From Date',
        'date_to'    => 'To Date',
        'show'       => 'Show',
        'reset'      => 'Reset',
        'all'        => '— All —',
    ],

    'columns' => [
        'school'     => 'School',
        'teacher'    => 'Teacher',
        'subject'    => 'Subject',
        'class'      => 'Class',
        'period'     => 'Period',
        'date'       => 'Date',
        'time'       => 'Time',
        'form'       => 'Form',
        'supervisor' => 'Supervisor',
        'visit_type' => 'Visit Type',
        'notified'   => 'Notified?',
        'status'     => 'Status',
        'actions'    => 'Actions',
    ],

    'visit_type' => [
        'announced' => 'Announced',
        'secret'    => 'Secret',
    ],

    'visit_status' => [
        'scheduled'        => 'Scheduled',
        'secret'           => 'Secret',
        'teacher_notified' => 'Teacher Notified',
        'in_progress'      => 'In Progress',
        'completed'        => 'Completed',
        'postponed'        => 'Postponed',
        'cancelled'        => 'Cancelled',
        'missed'           => 'Missed',
    ],

    'yes' => 'Yes',
    'no'  => 'No',
    'dash' => '—',

    'form' => [
        'create_title' => 'Schedule Class Visit',
        'edit_title'   => 'Edit Class Visit',
        'school'       => 'School',
        'stage'        => 'Stage',
        'class'        => 'Class',
        'section'      => 'Section',
        'teacher'      => 'Teacher',
        'subject'      => 'Subject',
        'period'       => 'Period',
        'period_hint'  => 'Periods from the selected teacher\'s timetable are shown.',
        'period_none'  => '— No period —',
        'visit_date'   => 'Visit Date',
        'visit_time'   => 'Visit Time',
        'evaluation_form' => 'Evaluation Form',
        'form_hint'    => 'Only published class-visit forms are listed.',
        'visit_type'   => 'Visit Type',
        'notify_teacher' => 'Notify teacher of the visit',
        'notify_hint'  => 'The teacher will not be notified for a secret visit.',
        'pre_notes'    => 'Pre-visit Notes',
        'save'         => 'Save',
        'cancel'       => 'Cancel',
        'select'       => '— Select —',
    ],

    'actions' => [
        'edit'    => 'Edit',
        'delete'  => 'Delete',
        'execute' => 'Execute Visit',
        'view_eval' => 'View Evaluation',
    ],

    'empty' => [
        'title'    => 'No class visits',
        'subtitle' => 'Start by scheduling a new class visit.',
    ],

    'flash' => [
        'created'   => 'Visit scheduled successfully.',
        'updated'   => 'Visit updated successfully.',
        'deleted'   => 'Visit deleted.',
        'not_found' => 'Visit not found.',
    ],

    'errors' => [
        'duplicate_slot'     => 'A visit is already scheduled for the same teacher in this period and date.',
        'form_not_eligible'  => 'The selected form is not eligible for class visits (must be published and class-visit-only).',
        'period_not_teacher' => 'The selected period does not belong to the chosen teacher\'s timetable.',
        'out_of_scope'       => 'The selected item does not belong to your school.',
        'completed_locked'   => 'A completed visit cannot be edited.',
        'delete_blocked'     => 'A completed visit cannot be deleted.',
        'cannot_execute_cancelled' => 'A cancelled visit cannot be executed.',
        'before_date'        => 'The visit cannot be executed before its date.',
        'no_form'            => 'No evaluation form is linked to this visit.',
    ],

    'confirm' => [
        'delete' => 'Are you sure you want to delete this visit?',
    ],
];
