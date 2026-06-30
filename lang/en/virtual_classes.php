<?php

return [
    // Titles
    'title'          => 'Virtual Classrooms',
    'create_title'   => 'New Session',
    'edit_title'     => 'Edit Session',
    'student_title'  => 'My Virtual Classes',

    // Breadcrumbs
    'breadcrumb_home' => 'Home',

    // Buttons
    'btn_create'      => 'New Session',
    'btn_save'        => 'Save',
    'btn_edit'        => 'Edit',
    'btn_show'        => 'View',
    'btn_cancel'      => 'Cancel',
    'btn_cancel_form' => 'Go Back',
    'btn_delete'      => 'Delete',

    // Fields
    'field_title'        => 'Session Title',
    'field_teacher'      => 'Teacher',
    'field_scheduled_at' => 'Scheduled At',
    'field_duration'     => 'Duration',
    'field_status'       => 'Status',
    'field_zoom'         => 'Zoom',
    'field_actions'      => 'Actions',
    'field_audience'     => 'Target Audience',
    'field_description'  => 'Description',
    'field_created_by'   => 'Created By',
    'field_created_at'   => 'Created At',
    'field_join'         => 'Join',

    // Minutes label
    'minutes' => 'min',

    // Status labels
    'status_scheduled' => 'Scheduled',
    'status_live'      => 'Live',
    'status_ended'     => 'Ended',
    'status_cancelled' => 'Cancelled',

    // Audience
    'audience_all'      => 'Everyone',
    'audience_students' => 'Students',
    'audience_parents'  => 'Parents',
    'audience_teachers' => 'Teachers',
    'audience_staff'    => 'Staff',

    // Target audience (mirrors announcements / school-calendar)
    'target_all'            => 'All users',
    'target_students'       => 'Students',
    'target_teachers'       => 'Teachers',
    'target_parents'        => 'Parents',
    'target_admins'         => 'Admins',
    'target_job_titles'     => 'Specific job titles',
    'target_specific_users' => 'Specific users',
    'target_specific_roles' => 'Specific roles',
    'target_pick_required'  => 'Select at least one target.',
    'class_attendance_hint' => 'Class used for the attendance roster (auto-filled when a single class is targeted for students).',

    // Zoom
    'zoom_linked'     => 'Linked',
    'zoom_none'       => 'Not linked',
    'zoom_info'       => 'Zoom Details',
    'zoom_meeting_id' => 'Meeting ID',
    'zoom_passcode'   => 'Passcode',
    'zoom_join'       => 'Join',
    'zoom_start'      => 'Start (Host)',
    'zoom_not_linked' => 'This session is not linked to a Zoom meeting.',

    // Select placeholder
    'select_teacher' => 'Select Teacher',

    // Flash messages
    'flash_created'         => 'Virtual class created and linked to Zoom.',
    'flash_created_no_zoom' => 'Session created but could not be linked to Zoom. Check connection settings.',
    'flash_updated'         => 'Virtual class updated.',
    'flash_cancelled'       => 'Virtual class cancelled.',
    'flash_deleted'         => 'Virtual class deleted.',

    // Confirm messages
    'confirm_cancel' => 'Are you sure you want to cancel this session?',
    'confirm_delete' => 'Are you sure you want to permanently delete this session?',

    // Empty states
    'empty'         => 'No virtual classes yet.',
    'student_empty' => 'No upcoming sessions at this time.',

    // Student view
    'join_not_yet' => 'Not yet joinable',

    // Tabs (card #234)
    'tab_today'    => "Today's Classes",
    'tab_recorded' => 'Recorded Classes',
    'tab_old'      => 'Past Classes',
    'tab_all'      => 'All Classes',

    // Platforms
    'field_platform'      => 'Platform',
    'platform_external'   => 'External Link',
    'platform_internal'   => 'Internal Platform',
    'select_platform'     => 'Select Platform',
    'field_external_url'  => 'External URL',

    // Class / subject
    'field_class'    => 'Class',
    'field_subject'  => 'Subject',
    'select_class'   => 'No specific class (everyone)',
    'select_subject' => 'No subject',

    // Start / join / started
    'field_started'   => 'Teacher Started',
    'btn_start'       => 'Start Class',
    'btn_join'        => 'Join',
    'started_yes'     => 'Started',
    'started_no'      => 'Not started',

    // Attendance window / actions
    'btn_attendance'        => 'View Attendance',
    'attendance_title'      => 'Attendance',
    'btn_recalc'            => 'Recalculate Attendance',
    'btn_export'            => 'Export to CSV',
    'btn_clear_cache'       => 'Clear Cache',
    'attendance_search'     => 'Search by student name…',
    'attendance_empty'      => 'No attendance recorded for this class yet.',

    // Attendance columns
    'att_student'  => 'Student',
    'att_teacher'  => 'Teacher',
    'att_subject'  => 'Subject',
    'att_class'    => 'Class',
    'att_joined'   => 'Join Time',
    'att_left'     => 'Leave Time',
    'att_duration' => 'Duration (min)',
    'att_status'   => 'Status',

    // Attendance statuses (colored)
    'att_present' => 'Present',
    'att_absent'  => 'Absent',
    'att_late'    => 'Late',
    'att_partial' => 'Partial',

    // Summary
    'summary_present' => 'Present',
    'summary_absent'  => 'Absent',
    'summary_late'    => 'Late',
    'summary_partial' => 'Partial',
    'summary_total'   => 'Total',
    'summary_none'    => 'Attendance not calculated yet. Click "Recalculate Attendance".',

    // Flash
    'flash_recalc'        => 'Attendance recalculated: :present present, :absent absent.',
    'flash_cache_cleared' => 'Virtual class cache cleared.',
    'join_window_hint'    => 'The join button appears only 5 minutes before the start time.',
];
