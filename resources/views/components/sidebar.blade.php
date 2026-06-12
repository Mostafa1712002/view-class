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

            {{-- === Card: ادارة المواد — single consolidated submenu (dedup: removed the duplicate under system settings) === --}}
            <li class="nav-item has-sub {{ (request()->routeIs('admin.subjects.*') || request()->routeIs('admin.subject-tracks.*') || request()->routeIs('admin.question-banks.*') || request()->routeIs('admin.exams.*') || request()->routeIs('admin.lessons.*') || request()->routeIs('manage.books.*')) ? 'active open' : '' }}" data-section="educational">
                <a href="#"><i class="la la-book"></i><span class="menu-title">@lang('shell.nav_exams_management')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.subjects.index') || request()->routeIs('admin.subjects.create') || request()->routeIs('admin.subjects.edit') ? 'active' : '' }}"><a href="{{ Route::has('admin.subjects.index') ? route('admin.subjects.index') : '#' }}"><i class="la la-book"></i><span class="menu-item">@lang('shell.nav_subjects')</span></a></li>
                    <li class="{{ request()->routeIs('admin.subject-tracks.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.subject-tracks.index') ? route('admin.subject-tracks.index') : '#' }}"><i class="la la-stream"></i><span class="menu-item">@lang('subject_tracks.page_title')</span></a></li>
                    <li class="{{ request()->routeIs('admin.question-banks.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.question-banks.index') ? route('admin.question-banks.index') : '#' }}"><i class="la la-question-circle"></i><span class="menu-item">@lang('shell.nav_questions_bank')</span></a></li>
                    <li class="{{ request()->routeIs('admin.exams.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.exams.index') ? route('admin.exams.index') : '#' }}"><i class="la la-file-alt"></i><span class="menu-item">@lang('shell.nav_exam_schedule')</span></a></li>
                    {{-- === Lessons card 64 === --}}<li class="{{ request()->routeIs('admin.lessons.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.lessons.index') ? route('admin.lessons.index') : '#' }}"><i class="la la-clock"></i><span class="menu-item">@lang('shell.nav_periods')</span></a></li>
                    {{-- === Books card 65 === --}}<li class="{{ request()->routeIs('manage.books.*') ? 'active' : '' }}"><a href="{{ Route::has('manage.books.index') ? route('manage.books.index') : '#' }}"><i class="la la-book-open"></i><span class="menu-item">@lang('shell.nav_books')</span></a></li>
                </ul>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.weekly-plans.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('manage.weekly-plans.index') ? route('manage.weekly-plans.index') : '#' }}"><i class="la la-list-alt"></i><span class="menu-title">@lang('shell.nav_weekly_plan')</span></a>
            </li>

            {{-- === Grades card 67 === --}}
            <li class="nav-item has-sub {{ (request()->routeIs('admin.grades.*') || request()->routeIs('admin.grade-reports.*') || request()->routeIs('admin.grades.entry.*')) ? 'active' : '' }}" data-section="educational">
                <a href="#"><i class="la la-graduation-cap"></i><span class="menu-title">@lang('shell.nav_grades')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.grade-reports.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.grade-reports.index') ? route('admin.grade-reports.index') : '#' }}"><i class="la la-file-alt"></i><span class="menu-item">تقارير الدرجات</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.grades.entry.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.grades.entry.index') ? route('admin.grades.entry.index') : '#' }}"><i class="la la-grid"></i><span class="menu-item">إدخال درجات (ديناميكي)</span></a>
                    </li>
                    <li class="{{ (request()->routeIs('admin.grades.index') || request()->routeIs('admin.grades.store') || request()->routeIs('admin.grades.publish')) ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.grades.index') ? route('admin.grades.index') : '#' }}"><i class="la la-edit"></i><span class="menu-item">إدخال الدرجات (مبسط)</span></a>
                    </li>
                </ul>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.schedules.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('manage.schedules.index') ? route('manage.schedules.index') : '#' }}"><i class="la la-calendar-check"></i><span class="menu-title">@lang('shell.nav_schedule')</span></a>
            </li>

            <li class="nav-item has-sub {{ request()->routeIs('admin.libraries.*') ? 'active open' : '' }}" data-section="educational">
                <a href="#"><i class="la la-bookmark"></i><span class="menu-title">@lang('shell.nav_libraries')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.libraries.public.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.public.index') ? route('admin.libraries.public.index') : '#' }}"><i class="la la-globe"></i><span class="menu-item">@lang('shell.nav_library_public')</span></a></li>
                    <li class="{{ request()->routeIs('admin.libraries.private.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.private.index') ? route('admin.libraries.private.index') : '#' }}"><i class="la la-lock"></i><span class="menu-item">@lang('shell.nav_library_private')</span></a></li>
                    <li class="{{ request()->routeIs('admin.libraries.labs.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.labs.index') ? route('admin.libraries.labs.index') : '#' }}"><i class="la la-flask"></i><span class="menu-item">@lang('shell.nav_labs')</span></a></li>
                </ul>
            </li>

            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-compass"></i><span class="menu-title">@lang('shell.nav_counseling')</span></a></li>

            <li class="nav-item has-sub {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" data-section="educational">
                <a href="#"><i class="la la-chart-bar"></i><span class="menu-title">@lang('shell.nav_reports')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.reports.administrative') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.reports.administrative') ? route('admin.reports.administrative') : '#' }}"><i class="la la-clipboard-list"></i><span class="menu-item">@lang('shell.nav_reports_admin')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.reports.statistical') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.reports.statistical') ? route('admin.reports.statistical') : '#' }}"><i class="la la-chart-line"></i><span class="menu-item">@lang('shell.nav_reports_stats')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.reports.user-reports') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.reports.user-reports') ? route('admin.reports.user-reports') : '#' }}"><i class="la la-users"></i><span class="menu-item">@lang('shell.nav_reports_users')</span></a>
                    </li>
                </ul>
            </li>

            {{-- === Appointments card #197 (Phase 1) === --}}
            <li class="nav-item has-sub {{ (request()->routeIs('manage.appointment-schedules.*') || request()->routeIs('admin.appointment-settings.*')) ? 'active open' : '' }}" data-section="educational">
                <a href="#"><i class="la la-calendar"></i><span class="menu-title">@lang('shell.nav_appointments')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('manage.appointment-schedules.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('manage.appointment-schedules.index') ? route('manage.appointment-schedules.index') : '#' }}">
                            <i class="la la-calendar-check"></i><span class="menu-item">@lang('shell.nav_my_appointments')</span>
                        </a>
                    </li>
                    @if($isStaff)
                    <li class="{{ request()->routeIs('admin.appointment-settings.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.appointment-settings.index') ? route('admin.appointment-settings.index') : '#' }}">
                            <i class="la la-cog"></i><span class="menu-item">@lang('shell.nav_appointments_settings')</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            <li class="nav-item" data-section="educational"><a href="#"><i class="la la-poll"></i><span class="menu-title">@lang('shell.nav_surveys')</span></a></li>
            <li class="nav-item {{ request()->routeIs('admin.evaluations.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.evaluations.index') ? route('admin.evaluations.index') : '#' }}"><i class="la la-clipboard-list"></i><span class="menu-title">@lang('shell.nav_eval_forms')</span></a></li>
            <li class="nav-item {{ request()->routeIs('admin.my-evaluations.*') || request()->routeIs('admin.evaluations.subjects') || request()->routeIs('admin.evaluations.execute.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.my-evaluations.index') ? route('admin.my-evaluations.index') : '#' }}"><i class="la la-star"></i><span class="menu-title">@lang('shell.nav_evaluations')</span></a></li>
            <li class="nav-item {{ request()->routeIs('admin.class-visits.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.class-visits.index') ? route('admin.class-visits.index') : '#' }}"><i class="la la-map-marker"></i><span class="menu-title">@lang('shell.nav_visits')</span></a></li>
            <li class="nav-item {{ request()->routeIs('admin.evaluations.approvals.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.evaluations.approvals.index') ? route('admin.evaluations.approvals.index') : '#' }}"><i class="la la-check-double"></i><span class="menu-title">@lang('shell.nav_eval_approvals')</span></a></li>
            <li class="nav-item has-sub {{ request()->routeIs('admin.eval-reports.*') || request()->routeIs('admin.job-performance.*') ? 'active open' : '' }}" data-section="educational">
                <a href="#"><i class="la la-chart-bar"></i><span class="menu-title">@lang('shell.nav_eval_reports')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.eval-reports.supervisors') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.supervisors') ? route('admin.eval-reports.supervisors') : '#' }}"><i class="la la-user-tie"></i><span class="menu-item">@lang('shell.nav_eval_rep_supervisors')</span></a></li>
                    <li class="{{ request()->routeIs('admin.eval-reports.supervisors-detailed') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.supervisors-detailed') ? route('admin.eval-reports.supervisors-detailed') : '#' }}"><i class="la la-list"></i><span class="menu-item">@lang('shell.nav_eval_rep_detailed')</span></a></li>
                    <li class="{{ request()->routeIs('admin.eval-reports.general-manager') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.general-manager') ? route('admin.eval-reports.general-manager') : '#' }}"><i class="la la-user-shield"></i><span class="menu-item">@lang('shell.nav_eval_rep_gm')</span></a></li>
                    <li class="{{ request()->routeIs('admin.job-performance.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.job-performance.index') ? route('admin.job-performance.index') : '#' }}"><i class="la la-briefcase"></i><span class="menu-item">@lang('shell.nav_job_performance')</span></a></li>
                    @if (Route::has('admin.eval-audit.index'))
                    <li class="{{ request()->routeIs('admin.eval-audit.*') ? 'active' : '' }}"><a href="{{ route('admin.eval-audit.index') }}"><i class="la la-history"></i><span class="menu-item">@lang('shell.nav_eval_audit')</span></a></li>
                    @endif
                </ul>
            </li>

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
            {{-- === Education policies cards #104/#105 === --}}
            <li class="nav-item {{ request()->routeIs('admin.policies.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('admin.policies.index') ? route('admin.policies.index') : '#' }}"><i class="la la-gavel"></i><span class="menu-title">@lang('shell.nav_policies')</span></a>
            </li>

            <li class="nav-item has-sub {{ request()->routeIs('admin.users.*') ? 'open' : '' }}" data-section="system">
                <a href="#"><i class="la la-users"></i><span class="menu-title">@lang('shell.nav_users')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.users.students.*') && !request()->routeIs('admin.users.students.global-search') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.students.index') ? route('admin.users.students.index') : '#' }}">
                            <i class="la la-user-graduate"></i><span class="menu-item">@lang('users.students')</span>
                        </a>
                    </li>
                    {{-- === School search card 59 === --}}@if(auth()->check() && auth()->user()->isSuperAdmin())<li class="{{ request()->routeIs('admin.users.students.global-search') ? 'active' : '' }}"><a href="{{ Route::has('admin.users.students.global-search') ? route('admin.users.students.global-search') : '#' }}"><i class="la la-search-plus"></i><span class="menu-item">@lang('users.global_search')</span></a></li>@endif
                    <li class="{{ request()->routeIs('admin.users.parents.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.parents.index') ? route('admin.users.parents.index') : '#' }}">
                            <i class="la la-user-friends"></i><span class="menu-item">@lang('users.parents')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.teachers.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.teachers.index') ? route('admin.users.teachers.index') : '#' }}">
                            <i class="la la-chalkboard-teacher"></i><span class="menu-item">@lang('users.teachers')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.admins.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.admins.index') ? route('admin.users.admins.index') : '#' }}">
                            <i class="la la-user-shield"></i><span class="menu-item">@lang('users.admins')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.cards.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.cards.index') ? route('admin.users.cards.index') : '#' }}">
                            <i class="la la-id-card"></i><span class="menu-item">@lang('users.cards')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.job-titles.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.job-titles.index') ? route('admin.users.job-titles.index') : '#' }}">
                            <i class="la la-tag"></i><span class="menu-item">@lang('users.job_titles')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.noor.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.noor.form') ? route('admin.noor.form') : '#' }}"><i class="la la-file-import"></i><span class="menu-item">@lang('shell.nav_users_import_noor')</span></a></li> {{-- === Noor card 58 === --}}
                </ul>
            </li>

            {{-- === Card: ادارة المواد — duplicate "إدارة المواد" submenu removed from here; consolidated into the educational section above === --}}

            <li class="nav-item {{ request()->routeIs('admin.school-schedule.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('admin.school-schedule.index') ? route('admin.school-schedule.index') : '#' }}">
                    <i class="la la-calendar-alt"></i><span class="menu-title">@lang('shell.nav_school_schedule')</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.academic-years.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('manage.academic-years.index') ? route('manage.academic-years.index') : '#' }}"><i class="la la-calendar"></i><span class="menu-title">@lang('shell.nav_academic_years')</span></a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.canteens.*') ? 'active' : '' }}" data-section="system"><a href="{{ route('admin.canteens.index') }}"><i class="la la-utensils"></i><span class="menu-title">@lang('shell.nav_cafeteria')</span></a></li>

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

            <li class="nav-item has-sub {{ request()->routeIs('admin.behavior.*') ? 'active open' : '' }}" data-section="system">
                <a href="#"><i class="la la-balance-scale"></i><span class="menu-title">@lang('shell.nav_behavior')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.behavior.groups.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.groups.index') }}"><i class="la la-users"></i><span class="menu-item">@lang('shell.nav_behavior_groups')</span></a></li>
                    <li class="{{ request()->routeIs('admin.behavior.behaviors.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.behaviors.index') }}"><i class="la la-gavel"></i><span class="menu-item">@lang('shell.nav_behaviors')</span></a></li>
                    <li class="{{ request()->routeIs('admin.behavior.actions.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.actions.index') }}"><i class="la la-cogs"></i><span class="menu-item">@lang('shell.nav_behavior_actions')</span></a></li>
                    <li class="{{ request()->routeIs('admin.behavior.records.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.records.index') }}"><i class="la la-clipboard-list"></i><span class="menu-item">@lang('behavior.records.title')</span></a></li>
                </ul>
            </li>

            @php
                $sidebarSupportRoute = ($sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin()))
                    ? (Route::has('admin.support.index') ? route('admin.support.index') : '#')
                    : (Route::has('my.support.index') ? route('my.support.index') : '#');
                $sidebarSupportActive = request()->routeIs('admin.support.*') || request()->routeIs('my.support.*');
            @endphp
            <li class="nav-item {{ $sidebarSupportActive ? 'active' : '' }}" data-section="system">
                <a href="{{ $sidebarSupportRoute }}"><i class="la la-life-ring"></i><span class="menu-title">@lang('shell.nav_support')</span></a>
            </li>
            <li class="nav-item" data-section="system"><a href="#"><i class="la la-user-plus"></i><span class="menu-title">@lang('shell.nav_admissions')</span></a></li>
            @endif

            {{-- My education policies — visible to every signed-in user (card #105); distinct
                 label from the admin management page to avoid the duplicate-name confusion (card #122) --}}
            <li class="nav-item {{ request()->routeIs('policies.my.*') ? 'active' : '' }}">
                <a href="{{ Route::has('policies.my.index') ? route('policies.my.index') : '#' }}"><i class="la la-gavel"></i><span class="menu-title">@lang('shell.nav_my_policies')</span></a>
            </li>

            {{-- Parent canteen controls — visible to parents (card #116) --}}
            @if($sidebarUser && $sidebarUser->hasRole('parent'))
            <li class="nav-item {{ request()->routeIs('my.canteen.*') ? 'active' : '' }}">
                <a href="{{ Route::has('my.canteen.index') ? route('my.canteen.index') : '#' }}"><i class="la la-utensils"></i><span class="menu-title">@lang('canteen.parent.title')</span></a>
            </li>
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
                <li class="nav-item {{ request()->routeIs('admin.my-evaluations.*') || request()->routeIs('admin.evaluations.subjects') || request()->routeIs('admin.evaluations.execute.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('admin.my-evaluations.index') ? route('admin.my-evaluations.index') : '#' }}"><i class="la la-star"></i><span class="menu-title">@lang('shell.nav_evaluations')</span></a>
                </li>
            @endif

            @if($sidebarUser && $sidebarUser->isStudent())
                <li class="navigation-header sec-educational"><span>@lang('shell.portal_student')</span></li>
                <li class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.dashboard') }}"><i class="la la-home"></i><span class="menu-title">@lang('shell.portal_dashboard')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.schedule') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.schedule') }}"><i class="la la-calendar-check"></i><span class="menu-title">@lang('shell.portal_my_schedule_link')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.exams') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.exams') }}"><i class="la la-file-alt"></i><span class="menu-title">@lang('shell.portal_exams')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.grades') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.grades') }}"><i class="la la-graduation-cap"></i><span class="menu-title">@lang('shell.portal_my_grades')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.attendance') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.attendance') }}"><i class="la la-check-square"></i><span class="menu-title">@lang('shell.portal_my_attendance')</span></a></li>
                {{-- === Books card 65 === --}}<li class="nav-item {{ request()->routeIs('student.books.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('student.books.index') ? route('student.books.index') : '#' }}"><i class="la la-book-open"></i><span class="menu-title">@lang('shell.nav_books')</span></a></li>
            @endif

            @if($sidebarUser && $sidebarUser->isParent())
                <li class="navigation-header sec-communication"><span>@lang('shell.portal_parent')</span></li>
                <li class="nav-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}" data-section="communication"><a href="{{ route('parent.dashboard') }}"><i class="la la-home"></i><span class="menu-title">@lang('shell.portal_dashboard')</span></a></li>
                <li class="nav-item {{ request()->routeIs('parent.contact-teacher') ? 'active' : '' }}" data-section="communication"><a href="{{ route('parent.contact-teacher') }}"><i class="la la-envelope"></i><span class="menu-title">@lang('shell.portal_contact_teacher')</span></a></li>
            @endif

        </ul>
    </div>
</div>
