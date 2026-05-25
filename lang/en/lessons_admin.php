<?php

return [
    'page_title' => 'Lessons',
    'index_title' => 'Lessons management',
    'breadcrumb_home' => 'Home',
    'breadcrumb_index' => 'Lessons',
    'breadcrumb_create' => 'Add lesson',
    'breadcrumb_edit' => 'Edit lesson',

    'actions' => [
        'add' => 'Add lesson',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'back' => 'Back',
    ],

    'kpi' => [
        'total' => 'Total lessons',
        'teachers' => 'Teachers',
        'subjects' => 'Subjects',
        'classes' => 'Classes',
    ],

    'filter' => [
        'title' => 'Filter lessons',
        'section' => 'Section',
        'class' => 'Class',
        'teacher' => 'Teacher',
        'subject' => 'Subject',
        'day' => 'Day',
        'search' => 'Search',
        'search_placeholder' => 'Search by teacher, subject or class…',
        'apply' => 'Apply',
        'reset' => 'Reset',
        'all' => 'All',
    ],

    'table' => [
        'teacher' => 'Teacher',
        'subject' => 'Subject',
        'section' => 'Section',
        'class' => 'Class',
        'day' => 'Day',
        'period' => 'Period',
        'time' => 'Time',
        'room' => 'Room',
        'substitute' => 'Substitute',
        'students' => 'Students',
        'actions' => 'Actions',
        'empty_title' => 'No lessons yet',
        'empty_hint' => 'Click "Add lesson" to create the first entry in the timetable.',
    ],

    'toolbar' => [
        'export' => 'Export timetable',
    ],

    'substitute' => [
        'none' => 'No substitute teacher',
        'badge' => 'Sub',
    ],

    'timeslots' => [
        'title' => 'Time slots',
        'add' => 'Add time slot',
        'period_no' => 'Period no.',
        'starts_at' => 'Start time',
        'ends_at' => 'End time',
        'is_break' => 'Break',
        'empty' => 'No time slots defined yet.',
        'confirm_delete' => 'Delete this time slot?',
    ],

    'advanced' => [
        'title' => 'Advanced board',
        'show' => 'Show board',
        'day_period' => 'Day / Period',
        'legend' => 'Lessons are laid out across weekdays and periods. Use the filters above to view a specific class, teacher or subject.',
    ],

    'students' => [
        'title' => 'Lesson students',
        'head' => 'Students of the lesson’s class',
        'name' => 'Name',
        'academic_no' => 'Academic no.',
        'empty' => 'No students enrolled in this lesson’s class.',
        'hint' => 'Select the students linked to this lesson. You can target support/enrichment lessons to a subset of the class.',
    ],

    'form' => [
        'create_title' => 'Add a new lesson',
        'edit_title' => 'Edit lesson',
        'create_subtitle' => 'Link a teacher with a subject, class, day and period.',
        'edit_subtitle' => 'Update this lesson’s data.',
        'class' => 'Class',
        'academic_year' => 'Academic year',
        'semester' => 'Semester',
        'semester_first' => 'First semester',
        'semester_second' => 'Second semester',
        'subject' => 'Subject',
        'teacher' => 'Teacher',
        'substitute_teacher' => 'Substitute teacher (optional)',
        'day' => 'Day',
        'period_number' => 'Period number',
        'start_time' => 'Start time',
        'end_time' => 'End time',
        'room' => 'Room (optional)',
        'choose' => 'Choose…',
    ],

    'days' => [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ],

    'flash' => [
        'created' => 'Lesson added successfully',
        'updated' => 'Lesson updated successfully',
        'deleted' => 'Lesson deleted successfully',
    ],

    'confirm_delete' => 'Delete this lesson?',

    'validation' => [
        'class_required' => 'Class is required',
        'class_invalid' => 'Invalid class',
        'year_required' => 'Academic year is required',
        'semester_required' => 'Semester is required',
        'subject_required' => 'Subject is required',
        'teacher_required' => 'Teacher is required',
        'day_required' => 'Day is required',
        'period_required' => 'Period number is required',
        'end_after_start' => 'End time must be after start time',
    ],

    'errors' => [
        'unauthorized' => 'You are not authorized to access this page',
    ],
];
