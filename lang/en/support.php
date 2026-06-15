<?php

return [
    // Page titles
    'my_tickets_title'         => 'Support Tickets',
    'create_ticket_title'      => 'Submit New Ticket',
    'ticket_detail_title'      => 'Ticket Details',
    'admin_tickets_title'      => 'Manage Support Tickets',
    'admin_ticket_detail_title'=> 'Ticket Details — Admin Panel',

    // Breadcrumbs
    'breadcrumb_home'          => 'Home',
    'breadcrumb_my_tickets'    => 'My Tickets',
    'breadcrumb_new_ticket'    => 'New Ticket',
    'breadcrumb_admin_tickets' => 'Support Tickets',

    // Fields
    'field_subject'            => 'Subject',
    'field_related_student'     => 'Related student',
    'placeholder_select_student' => 'Select student',
    'count_all'                 => 'All',
    'count_open'               => 'New',
    'count_in_progress'        => 'In progress',
    'count_resolved'           => 'Resolved',
    'count_closed'             => 'Closed',
    'field_category'           => 'Category',
    'field_status'             => 'Status',
    'field_priority'           => 'Priority',
    'field_body'               => 'Issue Details',
    'field_reply_body'         => 'Reply',
    'field_created_at'         => 'Created At',
    'field_last_reply_at'      => 'Last Reply',
    'field_creator'            => 'Submitted By',
    'field_assigned_to'        => 'Assigned To',
    'field_assign_to_user'     => 'Assign to User (ID)',
    'field_actions'            => 'Actions',

    // Statuses
    'status_open'              => 'Open',
    'status_in_progress'       => 'In Progress',
    'status_resolved'          => 'Resolved',
    'status_closed'            => 'Closed',

    // Priorities
    'priority_low'             => 'Low',
    'priority_normal'          => 'Normal',
    'priority_high'            => 'High',
    'priority_urgent'          => 'Urgent',

    // Categories
    'category_technical'       => 'Technical',
    'category_academic'        => 'Academic',
    'category_billing'         => 'Billing',
    'category_account'         => 'Account',
    'category_other'           => 'Other',

    // Buttons
    'btn_new_ticket'           => 'Submit New Ticket',
    'btn_submit_ticket'        => 'Submit Ticket',
    'btn_send_reply'           => 'Send Reply',
    'btn_view'                 => 'View',
    'btn_cancel'               => 'Cancel',
    'btn_assign'               => 'Assign',
    'btn_update_status'        => 'Update Status',

    // Sections
    'section_ticket_details'   => 'Ticket Details',
    'section_replies'          => 'Replies',
    'section_add_reply'        => 'Add Reply',
    'section_staff_reply'      => 'Staff Reply',
    'section_assign'           => 'Assign Agent',
    'section_change_status'    => 'Change Status',

    // Filters
    'filter_status'            => 'Status',
    'filter_priority'          => 'Priority',
    'filter_category'          => 'Category',
    'filter_all'               => 'All',
    'filter_apply'             => 'Search',
    'filter_reset'             => 'Reset',

    // Flash messages
    'flash_created'            => 'Support ticket submitted successfully.',
    'flash_reply_sent'         => 'Reply sent successfully.',
    'flash_assigned'           => 'Ticket assigned successfully.',
    'flash_status_updated'     => 'Ticket status updated successfully.',

    // Confirmations
    'confirm_submit'           => 'Submit this support ticket?',
    'confirm_assign'           => 'Assign this user to the ticket?',
    'confirm_status_change'    => 'Update this ticket\'s status?',

    // Misc
    'empty_tickets'            => 'No tickets found.',
    'ticket_closed_notice'     => 'This ticket is closed and cannot receive new replies.',
    'badge_staff'              => 'Staff',
    'placeholder_select_category' => 'Select category',
    'placeholder_user_id'      => 'User ID',

    // ─── #267 additions ───────────────────────────────────────────────────────

    'field_type'               => 'Ticket type',
    'placeholder_select_type'  => 'Select type',
    'type_bug'                 => 'Technical bug',
    'type_inquiry'             => 'Inquiry',
    'type_feature'             => 'Feature request',
    'type_activate_user'       => 'Activate user',
    'type_login_issue'         => 'Login issue',
    'type_reports_issue'       => 'Reports issue',
    'type_attendance_issue'    => 'Attendance issue',
    'type_certificates_issue'  => 'Certificates issue',
    'type_registration_issue'  => 'Registration issue',

    'field_department'         => 'Department',
    'placeholder_select_department' => 'Select department',
    'dept_assignments'         => 'Assignments',
    'dept_exams'               => 'Exams',
    'dept_virtual_classes'     => 'Virtual classes',
    'dept_attendance'          => 'Attendance',
    'dept_messages'            => 'Messages',
    'dept_certificates'        => 'Certificates',
    'dept_admissions'          => 'Admissions',
    'dept_support'             => 'Support',
    'dept_other'               => 'Other',

    'field_problem_url'        => 'Problem link',
    'placeholder_problem_url'  => 'https://...',
    'field_attachment'         => 'Attachment',
    'field_attachments'        => 'Attachments',
    'btn_download_attachment'  => 'Download attachment',
    'attachment_hint'          => 'Max 5 MB (images, PDF, Word, Excel, text, ZIP).',
    'no_attachment'            => 'No attachment',

    'field_school'             => 'School',
    'field_ticket_no'          => 'Ticket #',

    'card_open'                => 'New',
    'card_in_progress'         => 'In progress',
    'card_admin_replied'       => 'Admin replied',
    'card_user_replied'        => 'User replied',
    'card_closed'              => 'Closed',
    'card_of_total'            => 'of total',

    'section_status_log'       => 'Status change log',
    'empty_status_log'         => 'No status changes yet.',
    'empty_replies'            => 'No replies yet.',

    'btn_close'                => 'Close',
    'btn_reopen'               => 'Reopen',
    'btn_delete'               => 'Delete',
    'confirm_close'            => 'Close this ticket?',
    'confirm_reopen'           => 'Reopen this ticket?',
    'confirm_delete'           => 'Permanently delete this ticket?',
    'section_actions'          => 'Actions',

    'flash_closed'             => 'Ticket closed.',
    'flash_reopened'           => 'Ticket reopened.',
    'flash_deleted'            => 'Ticket deleted.',

    'filter_type'              => 'Type',
    'filter_department'        => 'Department',

    'notify_action_view'       => 'View ticket',
    'notify_created_title'     => 'New support ticket',
    'notify_staff_reply_title' => 'New reply from support',
    'notify_user_reply_title'  => 'New user reply on ticket',
    'notify_status_title'      => 'Ticket status changed to: :status',
    'notify_assigned_title'    => 'A support ticket was assigned to you',
];
