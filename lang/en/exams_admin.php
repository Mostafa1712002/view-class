<?php

return [

    'page_title'    => 'Exam Schedule',
    'plural'        => 'Exams',
    'breadcrumb'    => 'Exams',

    'online_title'   => 'Online exams',
    'manage_online'  => 'Manage online exams',
    'schedule_hint'  => 'This page only shows exam dates. Creating exams, their questions, publishing and results is under «Exams» in Assessment.',
    'schedule_from'  => 'From date',
    'schedule_to'    => 'To date',
    'schedule_filter'=> 'Show',
    'schedule_date'  => 'Date',
    'schedule_time'  => 'Time',
    'schedule_empty' => 'No exams are scheduled in this period.',

    'tabs' => [
        'class_schedule' => 'Class Schedule',
        'exam_schedule'  => 'Exam Schedule',
    ],

    'kpis' => [
        'total'     => 'Total Exams',
        'published' => 'Published',
        'active'    => 'In Progress',
        'upcoming'  => 'Scheduled Upcoming',
    ],

    'filters' => [
        'title'        => 'Search Filters',
        'grade_level'  => 'Grade',
        'teacher'      => 'Teacher',
        'subject'      => 'Subject',
        'class'        => 'Class',
        'type'         => 'Type',
        'status'       => 'Status',
        'all_grades'   => 'All grades',
        'all_teachers' => 'All teachers',
        'all_subjects' => 'All subjects',
        'all_classes'  => 'All classes',
        'all_types'    => 'All types',
        'all_statuses' => 'All statuses',
        'show'         => 'Apply',
        'reset'        => 'Reset',
    ],

    'actions' => [
        'add'         => 'Add Exam',
        'view'        => 'View',
        'questions'   => 'Questions',
        'edit'        => 'Edit',
        'delete'      => 'Delete',
        'delete_confirm' => 'Delete this exam?',
    ],

    'columns' => [
        'title'       => 'Title',
        'subject'     => 'Subject',
        'class'       => 'Class',
        'teacher'     => 'Teacher',
        'type'        => 'Type',
        'questions'   => 'Questions',
        'total_marks' => 'Total Marks',
        'start_time'  => 'Start Time',
        'status'      => 'Status',
        'published'   => 'Published',
        'actions'     => 'Actions',
    ],

    'badges' => [
        'published'   => 'Published',
        'draft'       => 'Draft',
    ],

    'empty' => [
        'title'       => 'No exam schedule yet',
        'subtitle'    => 'No exams have been created, or no exams match the selected filters.',
        'cta'         => 'Create your first exam',
    ],

];
