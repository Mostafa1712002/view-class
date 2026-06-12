<?php

return [
    // Page titles
    'page_title'         => 'Appointments',
    'schedules_title'    => 'Appointment Schedules',
    'schedule_create'    => 'Add Appointment Schedule',
    'schedule_edit'      => 'Edit Appointment Schedule',
    'schedule_show'      => 'Appointment Schedule Details',
    'settings_title'     => 'Appointment Settings',
    'bookable_roles'     => 'Bookable Roles',

    // Breadcrumbs
    'breadcrumb_home'      => 'Home',
    'breadcrumb_schedules' => 'Appointment Schedules',
    'breadcrumb_settings'  => 'Appointment Settings',
    'breadcrumb_create'    => 'Add',
    'breadcrumb_edit'      => 'Edit',
    'breadcrumb_show'      => 'Details',

    // KPIs
    'kpi_total'    => 'Total Schedules',
    'kpi_active'   => 'Active',
    'kpi_inactive' => 'Inactive',
    'kpi_open'     => 'Open for Booking',

    // Form fields
    'field_title'            => 'Title',
    'field_date_from'        => 'From Date',
    'field_date_to'          => 'To Date',
    'field_days'             => 'Days of Week',
    'field_time_from'        => 'From Time',
    'field_time_to'          => 'To Time',
    'field_slot_minutes'     => 'Slot Duration (minutes)',
    'field_max_appointments' => 'Max Appointments',
    'field_location'         => 'Location / Room',
    'field_mode'             => 'Contact Method',
    'field_status'           => 'Status',
    'field_notes'            => 'Notes',
    'field_booking_open'     => 'Open Booking for Students',
    'field_owner'            => 'Staff Member',

    // Days of week
    'day_sun' => 'Sunday',
    'day_mon' => 'Monday',
    'day_tue' => 'Tuesday',
    'day_wed' => 'Wednesday',
    'day_thu' => 'Thursday',
    'day_fri' => 'Friday',
    'day_sat' => 'Saturday',

    'days' => [
        'sun' => 'Sun',
        'mon' => 'Mon',
        'tue' => 'Tue',
        'wed' => 'Wed',
        'thu' => 'Thu',
        'fri' => 'Fri',
        'sat' => 'Sat',
    ],

    // Modes
    'mode_in_person' => 'In Person',
    'mode_call'      => 'Phone Call',
    'mode_virtual'   => 'Virtual',

    'modes' => [
        'in_person' => 'In Person',
        'call'      => 'Phone Call',
        'virtual'   => 'Virtual',
    ],

    // Statuses
    'status_active'   => 'Active',
    'status_inactive' => 'Inactive',
    'status_expired'  => 'Expired',

    'statuses' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
        'expired'  => 'Expired',
    ],

    // Table headers
    'table_title'     => 'Title',
    'table_owner'     => 'Staff',
    'table_dates'     => 'Period',
    'table_time'      => 'Time',
    'table_slot'      => 'Slot Duration',
    'table_mode'      => 'Mode',
    'table_booked'    => 'Booked / Available',
    'table_status'    => 'Status',
    'table_booking'   => 'Booking',
    'table_actions'   => 'Actions',

    // Actions / Buttons
    'btn_add'           => 'Add Schedule',
    'btn_edit'          => 'Edit',
    'btn_delete'        => 'Delete',
    'btn_copy'          => 'Copy',
    'btn_show'          => 'Details',
    'btn_save'          => 'Save',
    'btn_cancel'        => 'Cancel',
    'btn_open_booking'  => 'Open Booking',
    'btn_close_booking' => 'Close Booking',
    'btn_add_role'      => 'Add Role',

    // Confirms
    'confirm_delete'       => 'Are you sure you want to delete this schedule?',
    'confirm_copy'         => 'Do you want to copy this schedule?',
    'confirm_toggle_open'  => 'Open booking for this schedule?',
    'confirm_toggle_close' => 'Close booking for this schedule?',
    'confirm_delete_role'  => 'Are you sure you want to delete this role?',

    // Flash messages
    'flash_created'          => 'Appointment schedule created successfully.',
    'flash_updated'          => 'Appointment schedule updated successfully.',
    'flash_deleted'          => 'Appointment schedule deleted.',
    'flash_copied'           => 'Schedule copied successfully.',
    'flash_booking_opened'   => 'Booking has been opened for this schedule.',
    'flash_booking_closed'   => 'Booking has been closed for this schedule.',
    'flash_role_created'     => 'Bookable role added.',
    'flash_role_updated'     => 'Bookable role updated.',
    'flash_role_deleted'     => 'Bookable role deleted.',
    'flash_role_activated'   => 'Role activated.',
    'flash_role_deactivated' => 'Role deactivated.',

    // Empty states
    'empty_title'       => 'No appointment schedules yet',
    'empty_hint'        => 'Add a schedule so students and parents can book appointments.',
    'empty_roles_title' => 'No bookable roles defined',
    'empty_roles_hint'  => 'Add roles that can receive appointment bookings.',

    // Schedule show — bookings placeholder
    'bookings_phase2' => 'Bookings list will be available in Phase 2.',

    // Slots info
    'slots_unlimited' => 'Unlimited',
    'slots_available' => ':n remaining',
    'min_label'       => 'min',

    // Bookable role fields
    'field_label'       => 'Label / Function',
    'field_target_type' => 'Target Type',
    'field_target_id'   => 'Target ID (optional)',
    'field_sort'        => 'Sort Order',
    'field_is_active'   => 'Active',

    // Target types
    'target_type_role'            => 'Role',
    'target_type_job_title'       => 'Job Title',
    'target_type_user'            => 'Specific User',
    'target_type_subject_teacher' => 'Subject Teacher',

    // Filter
    'filter_title'     => 'Filter',
    'filter_all'       => 'All',
    'filter_apply'     => 'Apply',
    'filter_reset'     => 'Reset',
    'filter_q'         => 'Search by title',
    'filter_q_student' => 'Search by student name',
    'filter_status'    => 'Status',
    'filter_mode'      => 'Mode',
    'filter_date_from' => 'From Date',
    'filter_date_to'   => 'To Date',

    // ── Phase 2: Booking Flow ──────────────────────────────────────────────

    // Page titles
    'my_bookings_title'     => 'My Appointments',
    'manage_bookings_title' => 'Manage Bookings',
    'booking_show_title'    => 'Booking Details',

    // Booking statuses
    'booking_status_requested' => 'Requested',
    'booking_status_confirmed' => 'Confirmed',
    'booking_status_rejected'  => 'Rejected',
    'booking_status_cancelled' => 'Cancelled',
    'booking_status_completed' => 'Completed',

    // Form fields — booking
    'field_bookable_role'    => 'Role / Function',
    'field_subject'          => 'Subject',
    'field_target_person'    => 'Target Person',
    'field_reason'           => 'Reason',
    'field_appointment_date' => 'Appointment Date',
    'field_appointment_time' => 'Appointment Time',
    'field_contact_method'   => 'Contact Method',
    'field_attachment'       => 'Attachment (optional)',
    'field_child'            => 'Student',
    'field_decided_by'       => 'Decided By',
    'field_decision_at'      => 'Decision Date',
    'field_decision_note'    => 'Decision Note',

    // Table headers — booking
    'table_bookable_role'  => 'Role',
    'table_target_person'  => 'Target Person',
    'table_date_time'      => 'Date & Time',
    'table_contact_method' => 'Contact',
    'table_student'        => 'Student',
    'table_booked_by'      => 'Booked By',

    // Placeholders
    'placeholder_select_role'    => 'Select a role',
    'placeholder_select_subject' => 'Select a subject',
    'placeholder_select_person'  => 'Select a person',
    'placeholder_select_child'   => 'Select a student',
    'placeholder_choose_file'    => 'Choose file',
    'placeholder_reject_note'    => 'Write reason for rejection...',

    // Sections
    'section_child'   => 'Select Student',
    'section_role'    => 'Select Role',
    'section_details' => 'Appointment Details',
    'booking_details_section' => 'Booking Details',
    'decision_section'        => 'Decision',
    'decision_action_title'   => 'Action',

    // Buttons — booking
    'btn_book_new'          => 'Book New Appointment',
    'btn_book_submit'       => 'Submit Booking',
    'btn_cancel_booking'    => 'Cancel',
    'btn_confirm_booking'   => 'Confirm Appointment',
    'btn_reject_booking'    => 'Reject Appointment',
    'btn_complete_booking'  => 'Mark as Completed',
    'btn_view_attachment'   => 'View Attachment',
    'btn_back_to_list'      => 'Back to List',

    // Confirms
    'booking_confirm_cancel'  => 'Cancel this appointment?',
    'booking_confirm_action'  => 'Confirm this appointment?',
    'booking_complete_action' => 'Mark this appointment as completed?',
    'booking_reject_action'   => 'Reject this appointment?',

    // Flash messages — booking
    'booking_flash_created'       => 'Appointment request submitted successfully.',
    'booking_invalid_target' => 'The selected person is not available for the chosen role.',
    'booking_flash_cancelled'     => 'Appointment cancelled.',
    'booking_flash_cancel_denied' => 'This appointment cannot be cancelled.',
    'booking_flash_confirmed'     => 'Appointment confirmed.',
    'booking_flash_rejected'      => 'Appointment rejected.',
    'booking_flash_completed'     => 'Appointment marked as completed.',

    // Empty states — booking
    'booking_empty_title'  => 'No appointments yet',
    'booking_empty_hint'   => 'Book your first appointment using the button below.',
    'manage_empty_title'   => 'No bookings yet',
    'manage_empty_hint'    => 'No booking requests have been received yet.',

    // Labels
    'label_booking'             => 'booking(s)',
    'label_all_school_bookings' => 'All school bookings',
];
