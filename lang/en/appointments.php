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
    'filter_status'    => 'Status',
    'filter_mode'      => 'Mode',
    'filter_date_from' => 'From Date',
    'filter_date_to'   => 'To Date',
];
