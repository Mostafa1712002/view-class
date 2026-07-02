<?php

return [
    // Titles
    'title'         => 'School Calendar',
    'teacher_title' => 'Teacher Calendar',
    'create_title'  => 'Add Event',
    'edit_title'    => 'Edit Event',

    // Teacher calendar legend
    'legend_lesson'        => 'Lesson',
    'legend_exam'          => 'Exam',
    'legend_assignment'    => 'Assignment',
    'legend_virtual_class' => 'Virtual Class',
    'legend_appointment'   => 'Appointment',
    'legend_school_event'  => 'School Event',

    // Breadcrumbs
    'breadcrumb_home' => 'Home',

    // Buttons
    'btn_add'    => 'Add Event',
    'btn_save'   => 'Save',
    'btn_cancel' => 'Cancel',
    'btn_delete' => 'Delete',
    'btn_close'  => 'Close',

    // Fields
    'field_title'      => 'Event Title',
    'field_type'       => 'Event Type',
    'field_start_date' => 'Start Date',
    'field_end_date'   => 'End Date',
    'field_all_day'    => 'All Day',
    'field_start_time' => 'Start Time',
    'field_end_time'   => 'End Time',
    'field_color'      => 'Color',
    'field_audience'   => 'Target Audience',
    'field_location'   => 'Location',
    'field_description' => 'Description',
    'field_actions'    => 'Actions',

    // Event types
    'type_general'       => 'General Event',
    'type_private'       => 'Private Event',
    'type_holiday'       => 'Holiday',
    'type_exam'          => 'Exam',
    'type_meeting'       => 'Meeting',
    'type_activity'      => 'Activity',
    'type_admin'         => 'Administrative Appointment',
    'type_virtual_class' => 'Virtual Class',
    'type_alert'         => 'Alert',
    'type_occasion'      => 'School Occasion',
    'type_other'         => 'Other',

    // Audience
    'audience_all'      => 'Everyone',
    'audience_students' => 'Students',
    'audience_parents'  => 'Parents',
    'audience_teachers' => 'Teachers',
    'audience_staff'    => 'Staff',

    // Colors
    'color_auto'   => 'Auto (by type)',
    'color_red'    => 'Red',
    'color_orange' => 'Orange',
    'color_yellow' => 'Yellow',
    'color_green'  => 'Green',
    'color_blue'   => 'Blue',
    'color_purple' => 'Purple',
    'color_gray'   => 'Gray',

    // Flash messages
    'flash_created' => 'Event created successfully.',
    'flash_updated' => 'Event updated successfully.',
    'flash_deleted' => 'Event deleted successfully.',

    // Print
    'btn_print'    => 'Print',
    'print_title'  => 'School Calendar',
    'print_range'  => 'Period',
    'print_day'    => 'Print Daily',
    'print_week'   => 'Print Weekly',
    'print_month'  => 'Print Monthly',
    'view_day'     => 'Daily',
    'view_week'    => 'Weekly',
    'view_month'   => 'Monthly',

    // Validation
    'err_end_before_start'      => 'End date cannot be before the start date.',
    'err_end_time_before_start' => 'End time cannot be before the start time on the same day.',

    // Targeting
    'target_heading'   => 'Target Audience',
    'target_school'    => 'Whole School',
    'target_specific'  => 'Specific Users',
    'field_school'     => 'School',
    'school_hint'      => 'Choosing a school filters the available classes and users.',
    'field_grades'     => 'Grades',
    'field_classes'    => 'Classes',
    'field_users'      => 'Specific Users',
    'users_hint'       => 'Hold Ctrl to select multiple users.',
    'grade'            => 'Grade',

    // Notifications
    'notif_heading'    => 'Notifications & Reminder',
    'field_notify'     => 'Send a notification on creation?',
    'field_remind'     => 'Send a reminder before the event?',
    'field_remind_when' => 'Reminder lead time',
    'minutes'          => 'minutes',
    'hours'            => 'hours',
    'one_day'          => 'One day',
    'notif_new_title'    => 'New calendar event',
    'notif_reminder_title' => 'Upcoming event reminder',
    'notif_action'     => 'View Calendar',

    // Validation
    'err_specific_required' => 'Select grades, classes or users when targeting specific users.',

    // Misc
    'upcoming_events'   => 'Upcoming Events (3 months)',
    'no_events'         => 'No upcoming events.',
    'confirm_delete'    => 'Are you sure you want to delete ":title"?',
    'access_denied'     => 'You are not authorized to access this event.',
];
