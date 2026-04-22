@php
    $sidebarUser = auth()->user();
    $isStaff = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin());
@endphp
<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">

            {{-- Dashboard --}}
            <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <i class="la la-home"></i>
                    <span class="menu-title">@lang('shell.nav_dashboard')</span>
                </a>
            </li>

            @if($isStaff)
            {{-- ========== 1. برامج نوعية ========== --}}
            <li class="navigation-header sec-programs">
                <span>@lang('shell.section_programs')</span>
            </li>
            <li class="nav-item" data-section="programs"><a href="#"><i class="la la-lightbulb"></i><span class="menu-title">@lang('shell.nav_ana_wa_qadarat')</span></a></li>
            <li class="nav-item" data-section="programs"><a href="#"><i class="la la-flag"></i><span class="menu-title">@lang('shell.nav_alawwal')</span></a></li>
            <li class="nav-item" data-section="programs"><a href="#"><i class="la la-book-reader"></i><span class="menu-title">@lang('shell.nav_speed_reading')</span></a></li>

            {{-- ========== 2. عمليات تعليمية ========== --}}
            <li class="navigation-header sec-educational">
                <span>@lang('shell.section_educational')</span>
            </li>

            <li class="nav-item has-sub" data-section="educational">
                <a href="#"><i class="la la-book"></i><span class="menu-title">@lang('shell.nav_exams_management')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('manage.subjects.*') ? 'active' : '' }}"><a href="{{ Route::has('manage.subjects.index') ? route('manage.subjects.index') : '#' }}"><i class="la la-book"></i><span class="menu-item">@lang('shell.nav_subjects')</span></a></li>
                    <li><a href="#"><i class="la la-question-circle"></i><span class="menu-item">@lang('shell.nav_questions_bank')</span></a></li>
                    <li class="{{ request()->routeIs('admin.exams.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.exams.index') ? route('admin.exams.index') : '#' }}"><i class="la la-file-alt"></i><span class="menu-item">@lang('shell.nav_exam_schedule')</span></a></li>
                    <li><a href="#"><i class="la la-clock"></i><span class="menu-item">@lang('shell.nav_periods')</span></a></li>
                    <li><a href="#"><i class="la la-book-open"></i><span class="menu-item">@lang('shell.nav_books')</span></a></li>
                </ul>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.weekly-plans.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('manage.weekly-plans.index') ? route('manage.weekly-plans.index') : '#' }}"><i class="la la-list-alt"></i><span class="menu-title">@lang('shell.nav_weekly_plan')</span></a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('admin.grades.index') ? route('admin.grades.index') : '#' }}"><i class="la la-graduation-cap"></i><span class="menu-title">@lang('shell.nav_grades')</span></a>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.schedules.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('manage.schedules.index') ? route('manage.schedules.index') : '#' }}"><i class="la la-calendar-check"></i><span class="menu-title">@lang('shell.nav_schedule')</span></a>
            </li>

            <li class="nav-item has-sub" data-section="educational">
                <a href="#"><i class="la la-bookmark"></i><span class="menu-title">@lang('shell.nav_libraries')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><i class="la la-globe"></i><span class="menu-item">@lang('shell.nav_library_public')</span></a></li>
                    <li><a href="#"><i class="la la-lock"></i><span class="menu-item">@lang('shell.nav_library_private')</span></a></li>
                    <li><a href="#"><i class="la la-flask"></i><span class="menu-item">@lang('shell.nav_labs')</span></a></li>
                </ul>
            </li>

            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-compass"></i><span class="menu-title">@lang('shell.nav_counseling')</span></a></li>

            <li class="nav-item has-sub" data-section="educational">
                <a href="#"><i class="la la-chart-bar"></i><span class="menu-title">@lang('shell.nav_reports')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.reports.index') ? 'active' : '' }}"><a href="{{ Route::has('admin.reports.index') ? route('admin.reports.index') : '#' }}"><i class="la la-clipboard-list"></i><span class="menu-item">@lang('shell.nav_reports_admin')</span></a></li>
                    <li class="{{ request()->routeIs('admin.reports.analytics') ? 'active' : '' }}"><a href="{{ Route::has('admin.reports.analytics') ? route('admin.reports.analytics') : '#' }}"><i class="la la-chart-line"></i><span class="menu-item">@lang('shell.nav_reports_stats')</span></a></li>
                    <li><a href="#"><i class="la la-users"></i><span class="menu-item">@lang('shell.nav_reports_users')</span></a></li>
                </ul>
            </li>

            <li class="nav-item has-sub" data-section="educational">
                <a href="#"><i class="la la-calendar"></i><span class="menu-title">@lang('shell.nav_appointments')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><i class="la la-cog"></i><span class="menu-item">@lang('shell.nav_appointments_settings')</span></a></li>
                    <li><a href="#"><i class="la la-calendar-check"></i><span class="menu-item">@lang('shell.nav_my_appointments')</span></a></li>
                </ul>
            </li>

            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-poll"></i><span class="menu-title">@lang('shell.nav_surveys')</span></a></li>
            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-star"></i><span class="menu-title">@lang('shell.nav_evaluations')</span></a></li>
            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-map-marker"></i><span class="menu-title">@lang('shell.nav_visits')</span></a></li>

            <li class="nav-item has-sub" data-section="educational">
                <a href="#"><i class="la la-user-times"></i><span class="menu-title">@lang('shell.nav_attendance_management')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><i class="la la-file-alt"></i><span class="menu-item">@lang('shell.nav_attendance_report')</span></a></li>
                    <li><a href="#"><i class="la la-layer-group"></i><span class="menu-item">@lang('shell.nav_attendance_aggregate')</span></a></li>
                    <li><a href="#"><i class="la la-list"></i><span class="menu-item">@lang('shell.nav_attendance_list')</span></a></li>
                    <li><a href="#"><i class="la la-hourglass-half"></i><span class="menu-item">@lang('shell.nav_late_report')</span></a></li>
                    <li><a href="#"><i class="la la-gavel"></i><span class="menu-item">@lang('shell.nav_behavior_report')</span></a></li>
                    <li><a href="#"><i class="la la-tachometer-alt"></i><span class="menu-item">@lang('shell.nav_attendance_dashboard')</span></a></li>
                    <li class="{{ request()->routeIs('admin.attendance.index') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.index') ? route('admin.attendance.index') : '#' }}"><i class="la la-check-square"></i><span class="menu-item">@lang('shell.nav_daily_attendance')</span></a></li>
                    <li><a href="#"><i class="la la-stopwatch"></i><span class="menu-item">@lang('shell.nav_period_attendance')</span></a></li>
                    <li><a href="#"><i class="la la-binoculars"></i><span class="menu-item">@lang('shell.nav_follow_late_absence')</span></a></li>
                    <li><a href="#"><i class="la la-calendar-day"></i><span class="menu-item">@lang('shell.nav_days_absence_report')</span></a></li>
                    <li><a href="#"><i class="la la-clipboard-list"></i><span class="menu-item">@lang('shell.nav_subjects_absence_summary')</span></a></li>
                </ul>
            </li>

            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-user-clock"></i><span class="menu-title">@lang('shell.nav_teacher_absence')</span></a></li>
            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-certificate"></i><span class="menu-title">@lang('shell.nav_certificates')</span></a></li>
            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-external-link-alt"></i><span class="menu-title">@lang('shell.nav_edu_sites')</span></a></li>

            {{-- ========== 3. عمليات التواصل ========== --}}
            <li class="navigation-header sec-communication">
                <span>@lang('shell.section_communication')</span>
            </li>
            <li class="nav-item" data-section="communication"><a href="#"><i class="la la-bullhorn"></i><span class="menu-title">@lang('shell.nav_announcements')</span></a></li>
            <li class="nav-item" data-section="communication"><a href="#"><i class="la la-th-large"></i><span class="menu-title">@lang('shell.nav_classified_ads')</span></a></li>
            <li class="nav-item" data-section="communication"><a href="#"><i class="la la-calendar-alt"></i><span class="menu-title">@lang('shell.nav_calendar')</span></a></li>
            <li class="nav-item" data-section="communication"><a href="#"><i class="la la-video"></i><span class="menu-title">@lang('shell.nav_virtual_classrooms')</span></a></li>
            <li class="nav-item" data-section="communication"><a href="#"><i class="la la-comments"></i><span class="menu-title">@lang('shell.nav_discussion_rooms')</span></a></li>

            <li class="nav-item has-sub" data-section="communication">
                <a href="{{ route('messages.index') }}"><i class="la la-inbox"></i><span class="menu-title">@lang('shell.nav_mailbox')</span></a>
                <ul class="menu-content">
                    <li><a href="{{ route('messages.create') }}"><i class="la la-edit"></i><span class="menu-item">@lang('shell.nav_mail_new')</span></a></li>
                    <li class="{{ request()->routeIs('messages.index') ? 'active' : '' }}"><a href="{{ route('messages.index') }}"><i class="la la-inbox"></i><span class="menu-item">@lang('shell.nav_mail_inbox')</span></a></li>
                    <li><a href="#"><i class="la la-paper-plane"></i><span class="menu-item">@lang('shell.nav_mail_sent')</span></a></li>
                    <li><a href="#"><i class="la la-file"></i><span class="menu-item">@lang('shell.nav_mail_drafts')</span></a></li>
                    <li><a href="#"><i class="la la-archive"></i><span class="menu-item">@lang('shell.nav_mail_archive')</span></a></li>
                    <li><a href="#"><i class="la la-trash"></i><span class="menu-item">@lang('shell.nav_mail_trash')</span></a></li>
                </ul>
            </li>

            <li class="nav-item has-sub" data-section="communication">
                <a href="#"><i class="la la-mobile-alt"></i><span class="menu-title">@lang('shell.nav_sms')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><i class="la la-paper-plane"></i><span class="menu-item">@lang('shell.nav_sms_send')</span></a></li>
                    <li><a href="#"><i class="la la-comment-dots"></i><span class="menu-item">@lang('shell.nav_whatsapp')</span></a></li>
                    <li><a href="#"><i class="la la-file-excel"></i><span class="menu-item">@lang('shell.nav_sms_excel')</span></a></li>
                    <li><a href="#"><i class="la la-chart-bar"></i><span class="menu-item">@lang('shell.nav_sms_reports')</span></a></li>
                    <li><a href="#"><i class="la la-copy"></i><span class="menu-item">@lang('shell.nav_sms_templates')</span></a></li>
                    <li><a href="#"><i class="la la-file-alt"></i><span class="menu-item">@lang('shell.nav_sms_forms')</span></a></li>
                    <li><a href="#"><i class="la la-cog"></i><span class="menu-item">@lang('shell.nav_sms_settings')</span></a></li>
                    <li><a href="#"><i class="la la-plus-circle"></i><span class="menu-item">@lang('shell.nav_sms_extra')</span></a></li>
                </ul>
            </li>

            <li class="nav-item has-sub" data-section="communication">
                <a href="#"><i class="la la-handshake"></i><span class="menu-title">@lang('shell.nav_crm')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><i class="la la-user-friends"></i><span class="menu-item">@lang('shell.nav_parent_contact')</span></a></li>
                </ul>
            </li>

            {{-- ========== 4. إعدادات النظام ========== --}}
            <li class="navigation-header sec-system">
                <span>@lang('shell.section_system_settings')</span>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('admin.schools.index') ? route('admin.schools.index') : '#' }}"><i class="la la-building"></i><span class="menu-title">@lang('shell.nav_schools')</span></a>
            </li>

            <li class="nav-item has-sub" data-section="system">
                <a href="{{ Route::has('manage.users.index') ? route('manage.users.index') : '#' }}"><i class="la la-users"></i><span class="menu-title">@lang('shell.nav_users')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><i class="la la-user-shield"></i><span class="menu-item">@lang('shell.nav_users_admin')</span></a></li>
                    <li><a href="#"><i class="la la-chalkboard-teacher"></i><span class="menu-item">@lang('shell.nav_users_teachers')</span></a></li>
                    <li><a href="#"><i class="la la-user-friends"></i><span class="menu-item">@lang('shell.nav_users_parents')</span></a></li>
                    <li><a href="#"><i class="la la-user-graduate"></i><span class="menu-item">@lang('shell.nav_users_students')</span></a></li>
                    <li><a href="#"><i class="la la-file-import"></i><span class="menu-item">@lang('shell.nav_users_import_noor')</span></a></li>
                    <li><a href="#"><i class="la la-id-card"></i><span class="menu-item">@lang('shell.nav_users_cards')</span></a></li>
                    <li><a href="#"><i class="la la-search"></i><span class="menu-item">@lang('shell.nav_users_search_all')</span></a></li>
                </ul>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.academic-years.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('manage.academic-years.index') ? route('manage.academic-years.index') : '#' }}"><i class="la la-calendar"></i><span class="menu-title">@lang('shell.nav_academic_years')</span></a>
            </li>

            <li class="nav-item" data-section="system"><a href="#"><i class="la la-utensils"></i><span class="menu-title">@lang('shell.nav_cafeteria')</span></a></li>

            <li class="nav-item has-sub" data-section="system">
                <a href="#"><i class="la la-briefcase-medical"></i><span class="menu-title">@lang('shell.nav_clinic')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><i class="la la-hospital"></i><span class="menu-item">@lang('shell.nav_clinic_main')</span></a></li>
                    <li><a href="#"><i class="la la-bug"></i><span class="menu-item">@lang('shell.nav_diseases')</span></a></li>
                    <li><a href="#"><i class="la la-pills"></i><span class="menu-item">@lang('shell.nav_medicines')</span></a></li>
                    <li><a href="#"><i class="la la-syringe"></i><span class="menu-item">@lang('shell.nav_vaccinations')</span></a></li>
                    <li><a href="#"><i class="la la-notes-medical"></i><span class="menu-item">@lang('shell.nav_medical_records')</span></a></li>
                    <li><a href="#"><i class="la la-exchange-alt"></i><span class="menu-item">@lang('shell.nav_clinic_referrals')</span></a></li>
                    <li><a href="#"><i class="la la-stethoscope"></i><span class="menu-item">@lang('shell.nav_diagnoses')</span></a></li>
                </ul>
            </li>

            <li class="nav-item has-sub" data-section="system">
                <a href="#"><i class="la la-balance-scale"></i><span class="menu-title">@lang('shell.nav_behavior')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><i class="la la-users"></i><span class="menu-item">@lang('shell.nav_behavior_groups')</span></a></li>
                    <li><a href="#"><i class="la la-gavel"></i><span class="menu-item">@lang('shell.nav_behaviors')</span></a></li>
                    <li><a href="#"><i class="la la-cogs"></i><span class="menu-item">@lang('shell.nav_behavior_actions')</span></a></li>
                </ul>
            </li>

            <li class="nav-item" data-section="system"><a href="#"><i class="la la-life-ring"></i><span class="menu-title">@lang('shell.nav_support')</span></a></li>
            <li class="nav-item" data-section="system"><a href="#"><i class="la la-user-plus"></i><span class="menu-title">@lang('shell.nav_admissions')</span></a></li>
            @endif

            @if($sidebarUser && $sidebarUser->isTeacher())
                <li class="navigation-header sec-educational"><span>@lang('shell.portal_my_schedule')</span></li>
                <li class="nav-item {{ request()->routeIs('teacher.schedule') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.schedule') }}"><i class="la la-calendar-check"></i><span class="menu-title">@lang('shell.portal_my_schedule_link')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.weekly-plans.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.weekly-plans.index') }}"><i class="la la-list-alt"></i><span class="menu-title">@lang('shell.portal_my_weekly_plans')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.exams.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.exams.index') }}"><i class="la la-file-alt"></i><span class="menu-title">@lang('shell.portal_my_exams')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.grades.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.grades.index') }}"><i class="la la-graduation-cap"></i><span class="menu-title">@lang('shell.portal_enter_grades')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.attendance.index') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.attendance.index') }}"><i class="la la-check-square"></i><span class="menu-title">@lang('shell.portal_record_attendance')</span></a>
                </li>
            @endif

            @if($sidebarUser && $sidebarUser->isStudent())
                <li class="navigation-header sec-educational"><span>@lang('shell.portal_student')</span></li>
                <li class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.dashboard') }}"><i class="la la-home"></i><span class="menu-title">@lang('shell.portal_dashboard')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.schedule') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.schedule') }}"><i class="la la-calendar-check"></i><span class="menu-title">@lang('shell.portal_my_schedule_link')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.exams') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.exams') }}"><i class="la la-file-alt"></i><span class="menu-title">@lang('shell.portal_exams')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.grades') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.grades') }}"><i class="la la-graduation-cap"></i><span class="menu-title">@lang('shell.portal_my_grades')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.attendance') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.attendance') }}"><i class="la la-check-square"></i><span class="menu-title">@lang('shell.portal_my_attendance')</span></a></li>
            @endif

            @if($sidebarUser && $sidebarUser->isParent())
                <li class="navigation-header sec-communication"><span>@lang('shell.portal_parent')</span></li>
                <li class="nav-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}" data-section="communication"><a href="{{ route('parent.dashboard') }}"><i class="la la-home"></i><span class="menu-title">@lang('shell.portal_dashboard')</span></a></li>
                <li class="nav-item {{ request()->routeIs('parent.contact-teacher') ? 'active' : '' }}" data-section="communication"><a href="{{ route('parent.contact-teacher') }}"><i class="la la-envelope"></i><span class="menu-title">@lang('shell.portal_contact_teacher')</span></a></li>
            @endif

        </ul>
    </div>
</div>
