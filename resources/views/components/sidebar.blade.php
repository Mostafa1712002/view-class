@php
    $sidebarUser = auth()->user();
    $isStaff = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin());
@endphp

{{-- ===== Self-contained icon styles (no app.blade.php edits needed) ===== --}}
<style>
/* Bootstrap-Icons SVG sizing & baseline alignment */
.vc-ico {
    display: inline-block;
    vertical-align: -0.2em;
    flex-shrink: 0;
}

/* ---- section-based icon colours ---- */
/* Dashboard */
.nav-item a .vc-ico-dash   { color: #C9A227; }

/* Programs section */
.sec-programs  ~ li .vc-ico { color: #7c6bbd; }

/* Educational section */
.sec-educational ~ li .vc-ico { color: #2d9e6b; }

/* Communication section */
.sec-communication ~ li .vc-ico { color: #1e88e5; }

/* System settings section */
.sec-system ~ li .vc-ico { color: #e05a2b; }

/* Active item: always gold */
li.active > a .vc-ico,
li.active > a > .vc-ico {
    color: #C9A227 !important;
}

/* Submenu icons slightly smaller */
.menu-content .vc-ico {
    width: 16px;
    height: 16px;
}
</style>

<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">

            {{-- Dashboard --}}
            <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <x-svg-icon name="house" class="vc-ico-dash" />
                    <span class="menu-title">@lang('shell.nav_dashboard')</span>
                </a>
            </li>

            @if($isStaff)
            {{-- ========== 1. برامج نوعية ========== --}}
            <li class="navigation-header sec-programs">
                <span>@lang('shell.section_programs')</span>
            </li>
            <li class="nav-item" data-section="programs"><a href="#"><x-svg-icon name="lightbulb" class="vc-ico" /><span class="menu-title">@lang('shell.nav_ana_wa_qadarat')</span></a></li>
            <li class="nav-item" data-section="programs"><a href="#"><x-svg-icon name="flag" class="vc-ico" /><span class="menu-title">@lang('shell.nav_alawwal')</span></a></li>
            <li class="nav-item" data-section="programs"><a href="#"><x-svg-icon name="book" class="vc-ico" /><span class="menu-title">@lang('shell.nav_speed_reading')</span></a></li>

            {{-- ========== 2. عمليات تعليمية ========== --}}
            <li class="navigation-header sec-educational">
                <span>@lang('shell.section_educational')</span>
            </li>

            {{-- === Card: ادارة المواد — single consolidated submenu (dedup: removed the duplicate under system settings) === --}}
            <li class="nav-item has-sub {{ (request()->routeIs('admin.subjects.*') || request()->routeIs('admin.subject-tracks.*') || request()->routeIs('admin.question-banks.*') || request()->routeIs('admin.exams.*') || request()->routeIs('admin.lessons.*') || request()->routeIs('manage.books.*')) ? 'active open' : '' }}" data-section="educational">
                <a href="#"><x-svg-icon name="book" class="vc-ico" /><span class="menu-title">@lang('shell.nav_exams_management')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.subjects.index') || request()->routeIs('admin.subjects.create') || request()->routeIs('admin.subjects.edit') ? 'active' : '' }}"><a href="{{ Route::has('admin.subjects.index') ? route('admin.subjects.index') : '#' }}"><x-svg-icon name="book" class="vc-ico" /><span class="menu-item">@lang('shell.nav_subjects')</span></a></li>
                    <li class="{{ request()->routeIs('admin.subject-tracks.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.subject-tracks.index') ? route('admin.subject-tracks.index') : '#' }}"><x-svg-icon name="layout-text-sidebar" class="vc-ico" /><span class="menu-item">@lang('subject_tracks.page_title')</span></a></li>
                    <li class="{{ request()->routeIs('admin.question-banks.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.question-banks.index') ? route('admin.question-banks.index') : '#' }}"><x-svg-icon name="question-circle" class="vc-ico" /><span class="menu-item">@lang('shell.nav_questions_bank')</span></a></li>
                    <li class="{{ request()->routeIs('admin.exams.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.exams.index') ? route('admin.exams.index') : '#' }}"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-item">@lang('shell.nav_exam_schedule')</span></a></li>
                    {{-- === Lessons card 64 === --}}<li class="{{ request()->routeIs('admin.lessons.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.lessons.index') ? route('admin.lessons.index') : '#' }}"><x-svg-icon name="clock" class="vc-ico" /><span class="menu-item">@lang('shell.nav_periods')</span></a></li>
                    {{-- === Books card 65 === --}}<li class="{{ request()->routeIs('manage.books.*') ? 'active' : '' }}"><a href="{{ Route::has('manage.books.index') ? route('manage.books.index') : '#' }}"><x-svg-icon name="book-half" class="vc-ico" /><span class="menu-item">@lang('shell.nav_books')</span></a></li>
                </ul>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.weekly-plans.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('manage.weekly-plans.index') ? route('manage.weekly-plans.index') : '#' }}"><x-svg-icon name="list-ul" class="vc-ico" /><span class="menu-title">@lang('shell.nav_weekly_plan')</span></a>
            </li>

            {{-- === Grades card 67 === --}}
            <li class="nav-item has-sub {{ (request()->routeIs('admin.grades.*') || request()->routeIs('admin.grade-reports.*') || request()->routeIs('admin.grades.entry.*')) ? 'active' : '' }}" data-section="educational">
                <a href="#"><x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-title">@lang('shell.nav_grades')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.grade-reports.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.grade-reports.index') ? route('admin.grade-reports.index') : '#' }}"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-item">تقارير الدرجات</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.grades.entry.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.grades.entry.index') ? route('admin.grades.entry.index') : '#' }}"><x-svg-icon name="grid-3x3-gap" class="vc-ico" /><span class="menu-item">إدخال درجات (ديناميكي)</span></a>
                    </li>
                    <li class="{{ (request()->routeIs('admin.grades.index') || request()->routeIs('admin.grades.store') || request()->routeIs('admin.grades.publish')) ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.grades.index') ? route('admin.grades.index') : '#' }}"><x-svg-icon name="pencil" class="vc-ico" /><span class="menu-item">إدخال الدرجات (مبسط)</span></a>
                    </li>
                </ul>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.schedules.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('manage.schedules.index') ? route('manage.schedules.index') : '#' }}"><x-svg-icon name="calendar-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_schedule')</span></a>
            </li>

            <li class="nav-item has-sub {{ request()->routeIs('admin.libraries.*') ? 'active open' : '' }}" data-section="educational">
                <a href="#"><x-svg-icon name="bookmark" class="vc-ico" /><span class="menu-title">@lang('shell.nav_libraries')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.libraries.public.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.public.index') ? route('admin.libraries.public.index') : '#' }}"><x-svg-icon name="globe" class="vc-ico" /><span class="menu-item">@lang('shell.nav_library_public')</span></a></li>
                    <li class="{{ request()->routeIs('admin.libraries.private.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.private.index') ? route('admin.libraries.private.index') : '#' }}"><x-svg-icon name="lock" class="vc-ico" /><span class="menu-item">@lang('shell.nav_library_private')</span></a></li>
                    <li class="{{ request()->routeIs('admin.libraries.labs.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.labs.index') ? route('admin.libraries.labs.index') : '#' }}"><x-svg-icon name="eyedropper" class="vc-ico" /><span class="menu-item">@lang('shell.nav_labs')</span></a></li>
                </ul>
            </li>

            <li class="nav-item" data-section="educational"><a href="#"><x-svg-icon name="compass" class="vc-ico" /><span class="menu-title">@lang('shell.nav_counseling')</span></a></li>

            {{-- === Special Education module === --}}
            <li class="nav-item {{ request()->routeIs('manage.special-education.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('manage.special-education.index') ? route('manage.special-education.index') : '#' }}">
                    <x-svg-icon name="heart-pulse" class="vc-ico" /><span class="menu-title">@lang('special_education.title')</span>
                </a>
            </li>

            <li class="nav-item has-sub {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" data-section="educational">
                <a href="#"><x-svg-icon name="bar-chart" class="vc-ico" /><span class="menu-title">@lang('shell.nav_reports')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.reports.administrative') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.reports.administrative') ? route('admin.reports.administrative') : '#' }}"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-item">@lang('shell.nav_reports_admin')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.reports.statistical') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.reports.statistical') ? route('admin.reports.statistical') : '#' }}"><x-svg-icon name="graph-up" class="vc-ico" /><span class="menu-item">@lang('shell.nav_reports_stats')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('admin.reports.user-reports') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.reports.user-reports') ? route('admin.reports.user-reports') : '#' }}"><x-svg-icon name="people" class="vc-ico" /><span class="menu-item">@lang('shell.nav_reports_users')</span></a>
                    </li>
                </ul>
            </li>

            {{-- === Appointments card #197 (Phase 1) + #175/#184 (Phase 2) === --}}
            <li class="nav-item has-sub {{ (request()->routeIs('manage.appointment-schedules.*') || request()->routeIs('admin.appointment-settings.*') || request()->routeIs('manage.appointments.*')) ? 'active open' : '' }}" data-section="educational">
                <a href="#"><x-svg-icon name="calendar" class="vc-ico" /><span class="menu-title">@lang('shell.nav_appointments')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('manage.appointment-schedules.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('manage.appointment-schedules.index') ? route('manage.appointment-schedules.index') : '#' }}">
                            <x-svg-icon name="calendar-check" class="vc-ico" /><span class="menu-item">@lang('shell.nav_my_appointments')</span>
                        </a>
                    </li>
                    @if($isStaff)
                    <li class="{{ request()->routeIs('manage.appointments.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('manage.appointments.index') ? route('manage.appointments.index') : '#' }}">
                            <x-svg-icon name="list-ul" class="vc-ico" /><span class="menu-item">@lang('shell.nav_appointments_bookings')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.appointment-settings.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.appointment-settings.index') ? route('admin.appointment-settings.index') : '#' }}">
                            <x-svg-icon name="gear" class="vc-ico" /><span class="menu-item">@lang('shell.nav_appointments_settings')</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            @if($sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin()))
            <li class="nav-item {{ request()->routeIs('admin.surveys.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('admin.surveys.index') ? route('admin.surveys.index') : '#' }}">
                    <x-svg-icon name="bar-chart-line" class="vc-ico" /><span class="menu-title">@lang('shell.nav_surveys')</span>
                </a>
            </li>
            @else
            <li class="nav-item {{ request()->routeIs('my.surveys.*') ? 'active' : '' }}" data-section="educational">
                <a href="{{ Route::has('my.surveys.index') ? route('my.surveys.index') : '#' }}">
                    <x-svg-icon name="bar-chart-line" class="vc-ico" /><span class="menu-title">@lang('shell.nav_surveys')</span>
                </a>
            </li>
            @endif
            <li class="nav-item {{ request()->routeIs('admin.evaluations.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.evaluations.index') ? route('admin.evaluations.index') : '#' }}"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_eval_forms')</span></a></li>
            <li class="nav-item {{ request()->routeIs('admin.my-evaluations.*') || request()->routeIs('admin.evaluations.subjects') || request()->routeIs('admin.evaluations.execute.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.my-evaluations.index') ? route('admin.my-evaluations.index') : '#' }}"><x-svg-icon name="star" class="vc-ico" /><span class="menu-title">@lang('shell.nav_evaluations')</span></a></li>
            <li class="nav-item {{ request()->routeIs('admin.class-visits.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.class-visits.index') ? route('admin.class-visits.index') : '#' }}"><x-svg-icon name="geo-alt" class="vc-ico" /><span class="menu-title">@lang('shell.nav_visits')</span></a></li>
            <li class="nav-item {{ request()->routeIs('admin.evaluations.approvals.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.evaluations.approvals.index') ? route('admin.evaluations.approvals.index') : '#' }}"><x-svg-icon name="check2-all" class="vc-ico" /><span class="menu-title">@lang('shell.nav_eval_approvals')</span></a></li>
            <li class="nav-item has-sub {{ request()->routeIs('admin.eval-reports.*') || request()->routeIs('admin.job-performance.*') ? 'active open' : '' }}" data-section="educational">
                <a href="#"><x-svg-icon name="bar-chart" class="vc-ico" /><span class="menu-title">@lang('shell.nav_eval_reports')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.eval-reports.supervisors') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.supervisors') ? route('admin.eval-reports.supervisors') : '#' }}"><x-svg-icon name="person-badge" class="vc-ico" /><span class="menu-item">@lang('shell.nav_eval_rep_supervisors')</span></a></li>
                    <li class="{{ request()->routeIs('admin.eval-reports.supervisors-detailed') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.supervisors-detailed') ? route('admin.eval-reports.supervisors-detailed') : '#' }}"><x-svg-icon name="list" class="vc-ico" /><span class="menu-item">@lang('shell.nav_eval_rep_detailed')</span></a></li>
                    <li class="{{ request()->routeIs('admin.eval-reports.general-manager') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.general-manager') ? route('admin.eval-reports.general-manager') : '#' }}"><x-svg-icon name="shield-shaded" class="vc-ico" /><span class="menu-item">@lang('shell.nav_eval_rep_gm')</span></a></li>
                    <li class="{{ request()->routeIs('admin.job-performance.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.job-performance.index') ? route('admin.job-performance.index') : '#' }}"><x-svg-icon name="briefcase" class="vc-ico" /><span class="menu-item">@lang('shell.nav_job_performance')</span></a></li>
                    @if (Route::has('admin.eval-audit.index'))
                    <li class="{{ request()->routeIs('admin.eval-audit.*') ? 'active' : '' }}"><a href="{{ route('admin.eval-audit.index') }}"><x-svg-icon name="clock-history" class="vc-ico" /><span class="menu-item">@lang('shell.nav_eval_audit')</span></a></li>
                    @endif
                </ul>
            </li>

            <li class="nav-item has-sub" data-section="educational">
                <a href="#"><x-svg-icon name="person-x" class="vc-ico" /><span class="menu-title">@lang('shell.nav_attendance_management')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-item">@lang('shell.nav_attendance_report')</span></a></li>
                    <li><a href="#"><x-svg-icon name="stack" class="vc-ico" /><span class="menu-item">@lang('shell.nav_attendance_aggregate')</span></a></li>
                    <li><a href="#"><x-svg-icon name="list" class="vc-ico" /><span class="menu-item">@lang('shell.nav_attendance_list')</span></a></li>
                    <li><a href="#"><x-svg-icon name="hourglass-split" class="vc-ico" /><span class="menu-item">@lang('shell.nav_late_report')</span></a></li>
                    <li><a href="#"><x-svg-icon name="hammer" class="vc-ico" /><span class="menu-item">@lang('shell.nav_behavior_report')</span></a></li>
                    <li><a href="#"><x-svg-icon name="speedometer2" class="vc-ico" /><span class="menu-item">@lang('shell.nav_attendance_dashboard')</span></a></li>
                    <li class="{{ request()->routeIs('admin.attendance.index') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.index') ? route('admin.attendance.index') : '#' }}"><x-svg-icon name="check-square" class="vc-ico" /><span class="menu-item">@lang('shell.nav_daily_attendance')</span></a></li>
                    <li><a href="#"><x-svg-icon name="stopwatch" class="vc-ico" /><span class="menu-item">@lang('shell.nav_period_attendance')</span></a></li>
                    <li><a href="#"><x-svg-icon name="binoculars" class="vc-ico" /><span class="menu-item">@lang('shell.nav_follow_late_absence')</span></a></li>
                    <li><a href="#"><x-svg-icon name="calendar-day" class="vc-ico" /><span class="menu-item">@lang('shell.nav_days_absence_report')</span></a></li>
                    <li><a href="#"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-item">@lang('shell.nav_subjects_absence_summary')</span></a></li>
                </ul>
            </li>

            <li class="nav-item" data-section="educational"><a href="#"><x-svg-icon name="person-badge" class="vc-ico" /><span class="menu-title">@lang('shell.nav_teacher_absence')</span></a></li>
            <li class="nav-item" data-section="educational"><a href="#"><x-svg-icon name="award" class="vc-ico" /><span class="menu-title">@lang('shell.nav_certificates')</span></a></li>
            <li class="nav-item" data-section="educational"><a href="#"><x-svg-icon name="box-arrow-up-right" class="vc-ico" /><span class="menu-title">@lang('shell.nav_edu_sites')</span></a></li>

            {{-- ========== 3. عمليات التواصل ========== --}}
            <li class="navigation-header sec-communication">
                <span>@lang('shell.section_communication')</span>
            </li>
            <li class="nav-item" data-section="communication"><a href="#"><x-svg-icon name="megaphone" class="vc-ico" /><span class="menu-title">@lang('shell.nav_announcements')</span></a></li>
            <li class="nav-item" data-section="communication"><a href="#"><x-svg-icon name="grid" class="vc-ico" /><span class="menu-title">@lang('shell.nav_classified_ads')</span></a></li>
            <li class="nav-item" data-section="communication"><a href="#"><x-svg-icon name="calendar-week" class="vc-ico" /><span class="menu-title">@lang('shell.nav_calendar')</span></a></li>
            @php
                $vcIsStaff  = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin() || $sidebarUser->isTeacher());
                $vcRoute    = $vcIsStaff
                    ? (Route::has('manage.virtual-classes.index') ? route('manage.virtual-classes.index') : '#')
                    : (Route::has('my.virtual-classes.index') ? route('my.virtual-classes.index') : '#');
                $vcActive   = request()->routeIs('manage.virtual-classes.*') || request()->routeIs('my.virtual-classes.*');
            @endphp
            <li class="nav-item {{ $vcActive ? 'active' : '' }}" data-section="communication">
                <a href="{{ $vcRoute }}"><x-svg-icon name="camera-video" class="vc-ico" /><span class="menu-title">@lang('shell.nav_virtual_classrooms')</span></a>
            </li>
            @php
                $discIsStaff   = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin() || $sidebarUser->isTeacher());
                $discRoute     = $discIsStaff
                    ? (Route::has('manage.discussion-rooms.index') ? route('manage.discussion-rooms.index') : '#')
                    : (Route::has('discussion.index') ? route('discussion.index') : '#');
                $discActive    = request()->routeIs('manage.discussion-rooms.*') || request()->routeIs('discussion.*');
            @endphp
            <li class="nav-item {{ $discActive ? 'active' : '' }}" data-section="communication">
                <a href="{{ $discRoute }}"><x-svg-icon name="chat-dots" class="vc-ico" /><span class="menu-title">@lang('shell.nav_discussion_rooms')</span></a>
            </li>

            {{-- Behaviour records — teacher-only link (#192). Admins reach it via the
                 behaviour section below; teachers get a direct link here. --}}
            @if($sidebarUser && $sidebarUser->isTeacher() && ! $sidebarUser->isSchoolAdmin() && ! $sidebarUser->isSuperAdmin())
            <li class="nav-item {{ request()->routeIs('admin.behavior.records.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ route('admin.behavior.records.index') }}"><x-svg-icon name="shield-check" class="vc-ico" /><span class="menu-title">@lang('behavior.records.title')</span></a>
            </li>
            @endif

            <li class="nav-item has-sub {{ request()->routeIs('my.mailbox.*') ? 'active open' : '' }}" data-section="communication">
                <a href="{{ Route::has('my.mailbox.index') ? route('my.mailbox.index') : '#' }}"><x-svg-icon name="inbox" class="vc-ico" /><span class="menu-title">@lang('shell.nav_mailbox')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('my.mailbox.create') ? 'active' : '' }}">
                        <a href="{{ Route::has('my.mailbox.create') ? route('my.mailbox.create') : '#' }}"><x-svg-icon name="pencil" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_new')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('my.mailbox.index') || (request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'inbox') ? 'active' : '' }}">
                        <a href="{{ Route::has('my.mailbox.index') ? route('my.mailbox.index') : '#' }}"><x-svg-icon name="inbox" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_inbox')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'sent' ? 'active' : '' }}">
                        <a href="{{ Route::has('my.mailbox.folder') ? route('my.mailbox.folder', 'sent') : '#' }}"><x-svg-icon name="send" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_sent')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'drafts' ? 'active' : '' }}">
                        <a href="{{ Route::has('my.mailbox.folder') ? route('my.mailbox.folder', 'drafts') : '#' }}"><x-svg-icon name="file" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_drafts')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'archive' ? 'active' : '' }}">
                        <a href="{{ Route::has('my.mailbox.folder') ? route('my.mailbox.folder', 'archive') : '#' }}"><x-svg-icon name="archive" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_archive')</span></a>
                    </li>
                    <li class="{{ request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'trash' ? 'active' : '' }}">
                        <a href="{{ Route::has('my.mailbox.folder') ? route('my.mailbox.folder', 'trash') : '#' }}"><x-svg-icon name="trash" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_trash')</span></a>
                    </li>
                </ul>
            </li>

            <li class="nav-item has-sub" data-section="communication">
                <a href="#"><x-svg-icon name="phone" class="vc-ico" /><span class="menu-title">@lang('shell.nav_sms')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><x-svg-icon name="send" class="vc-ico" /><span class="menu-item">@lang('shell.nav_sms_send')</span></a></li>
                    <li><a href="#"><x-svg-icon name="chat-dots" class="vc-ico" /><span class="menu-item">@lang('shell.nav_whatsapp')</span></a></li>
                    <li><a href="#"><x-svg-icon name="file-earmark-spreadsheet" class="vc-ico" /><span class="menu-item">@lang('shell.nav_sms_excel')</span></a></li>
                    <li><a href="#"><x-svg-icon name="bar-chart" class="vc-ico" /><span class="menu-item">@lang('shell.nav_sms_reports')</span></a></li>
                    <li><a href="#"><x-svg-icon name="copy" class="vc-ico" /><span class="menu-item">@lang('shell.nav_sms_templates')</span></a></li>
                    <li><a href="#"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-item">@lang('shell.nav_sms_forms')</span></a></li>
                    <li><a href="#"><x-svg-icon name="gear" class="vc-ico" /><span class="menu-item">@lang('shell.nav_sms_settings')</span></a></li>
                    <li><a href="#"><x-svg-icon name="plus-circle" class="vc-ico" /><span class="menu-item">@lang('shell.nav_sms_extra')</span></a></li>
                </ul>
            </li>

            <li class="nav-item has-sub" data-section="communication">
                <a href="#"><x-svg-icon name="hand-thumbs-up" class="vc-ico" /><span class="menu-title">@lang('shell.nav_crm')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><x-svg-icon name="people" class="vc-ico" /><span class="menu-item">@lang('shell.nav_parent_contact')</span></a></li>
                </ul>
            </li>

            {{-- ========== 4. إعدادات النظام ========== --}}
            <li class="navigation-header sec-system">
                <span>@lang('shell.section_system_settings')</span>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('admin.schools.index') ? route('admin.schools.index') : '#' }}"><x-svg-icon name="building" class="vc-ico" /><span class="menu-title">@lang('shell.nav_schools')</span></a>
            </li>
            {{-- === Education policies cards #104/#105 === --}}
            <li class="nav-item {{ request()->routeIs('admin.policies.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('admin.policies.index') ? route('admin.policies.index') : '#' }}"><x-svg-icon name="hammer" class="vc-ico" /><span class="menu-title">@lang('shell.nav_policies')</span></a>
            </li>

            <li class="nav-item has-sub {{ request()->routeIs('admin.users.*') ? 'open' : '' }}" data-section="system">
                <a href="#"><x-svg-icon name="people" class="vc-ico" /><span class="menu-title">@lang('shell.nav_users')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.users.students.*') && !request()->routeIs('admin.users.students.global-search') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.students.index') ? route('admin.users.students.index') : '#' }}">
                            <x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-item">@lang('users.students')</span>
                        </a>
                    </li>
                    {{-- === School search card 59 === --}}@if(auth()->check() && auth()->user()->isSuperAdmin())<li class="{{ request()->routeIs('admin.users.students.global-search') ? 'active' : '' }}"><a href="{{ Route::has('admin.users.students.global-search') ? route('admin.users.students.global-search') : '#' }}"><x-svg-icon name="search" class="vc-ico" /><span class="menu-item">@lang('users.global_search')</span></a></li>@endif
                    <li class="{{ request()->routeIs('admin.users.parents.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.parents.index') ? route('admin.users.parents.index') : '#' }}">
                            <x-svg-icon name="people" class="vc-ico" /><span class="menu-item">@lang('users.parents')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.teachers.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.teachers.index') ? route('admin.users.teachers.index') : '#' }}">
                            <x-svg-icon name="easel" class="vc-ico" /><span class="menu-item">@lang('users.teachers')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.admins.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.admins.index') ? route('admin.users.admins.index') : '#' }}">
                            <x-svg-icon name="shield-shaded" class="vc-ico" /><span class="menu-item">@lang('users.admins')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.cards.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.cards.index') ? route('admin.users.cards.index') : '#' }}">
                            <x-svg-icon name="person-vcard" class="vc-ico" /><span class="menu-item">@lang('users.cards')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.users.job-titles.*') ? 'active' : '' }}">
                        <a href="{{ Route::has('admin.users.job-titles.index') ? route('admin.users.job-titles.index') : '#' }}">
                            <x-svg-icon name="tag" class="vc-ico" /><span class="menu-item">@lang('users.job_titles')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.noor.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.noor.form') ? route('admin.noor.form') : '#' }}"><x-svg-icon name="file-earmark-arrow-down" class="vc-ico" /><span class="menu-item">@lang('shell.nav_users_import_noor')</span></a></li> {{-- === Noor card 58 === --}}
                </ul>
            </li>

            {{-- === Card: ادارة المواد — duplicate "إدارة المواد" submenu removed from here; consolidated into the educational section above === --}}

            <li class="nav-item {{ request()->routeIs('admin.school-schedule.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('admin.school-schedule.index') ? route('admin.school-schedule.index') : '#' }}">
                    <x-svg-icon name="calendar-week" class="vc-ico" /><span class="menu-title">@lang('shell.nav_school_schedule')</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.academic-years.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('manage.academic-years.index') ? route('manage.academic-years.index') : '#' }}"><x-svg-icon name="calendar" class="vc-ico" /><span class="menu-title">@lang('shell.nav_academic_years')</span></a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.canteens.*') ? 'active' : '' }}" data-section="system"><a href="{{ route('admin.canteens.index') }}"><x-svg-icon name="cup-hot" class="vc-ico" /><span class="menu-title">@lang('shell.nav_cafeteria')</span></a></li>

            <li class="nav-item has-sub" data-section="system">
                <a href="#"><x-svg-icon name="bag-plus" class="vc-ico" /><span class="menu-title">@lang('shell.nav_clinic')</span></a>
                <ul class="menu-content">
                    <li><a href="#"><x-svg-icon name="building-fill-x" class="vc-ico" /><span class="menu-item">@lang('shell.nav_clinic_main')</span></a></li>
                    <li><a href="#"><x-svg-icon name="bug" class="vc-ico" /><span class="menu-item">@lang('shell.nav_diseases')</span></a></li>
                    <li><a href="#"><x-svg-icon name="capsule" class="vc-ico" /><span class="menu-item">@lang('shell.nav_medicines')</span></a></li>
                    <li><a href="#"><x-svg-icon name="capsule-pill" class="vc-ico" /><span class="menu-item">@lang('shell.nav_vaccinations')</span></a></li>
                    <li><a href="#"><x-svg-icon name="clipboard2-pulse" class="vc-ico" /><span class="menu-item">@lang('shell.nav_medical_records')</span></a></li>
                    <li><a href="#"><x-svg-icon name="arrow-left-right" class="vc-ico" /><span class="menu-item">@lang('shell.nav_clinic_referrals')</span></a></li>
                    <li><a href="#"><x-svg-icon name="heart-pulse" class="vc-ico" /><span class="menu-item">@lang('shell.nav_diagnoses')</span></a></li>
                </ul>
            </li>

            <li class="nav-item has-sub {{ request()->routeIs('admin.behavior.*') ? 'active open' : '' }}" data-section="system">
                <a href="#"><x-svg-icon name="shield-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_behavior')</span></a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.behavior.groups.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.groups.index') }}"><x-svg-icon name="people" class="vc-ico" /><span class="menu-item">@lang('shell.nav_behavior_groups')</span></a></li>
                    <li class="{{ request()->routeIs('admin.behavior.behaviors.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.behaviors.index') }}"><x-svg-icon name="hammer" class="vc-ico" /><span class="menu-item">@lang('shell.nav_behaviors')</span></a></li>
                    <li class="{{ request()->routeIs('admin.behavior.actions.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.actions.index') }}"><x-svg-icon name="gear-fill" class="vc-ico" /><span class="menu-item">@lang('shell.nav_behavior_actions')</span></a></li>
                    <li class="{{ request()->routeIs('admin.behavior.records.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.records.index') }}"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-item">@lang('behavior.records.title')</span></a></li>
                </ul>
            </li>

            @php
                $sidebarSupportRoute = ($sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin()))
                    ? (Route::has('admin.support.index') ? route('admin.support.index') : '#')
                    : (Route::has('my.support.index') ? route('my.support.index') : '#');
                $sidebarSupportActive = request()->routeIs('admin.support.*') || request()->routeIs('my.support.*');
            @endphp
            <li class="nav-item {{ $sidebarSupportActive ? 'active' : '' }}" data-section="system">
                <a href="{{ $sidebarSupportRoute }}"><x-svg-icon name="life-preserver" class="vc-ico" /><span class="menu-title">@lang('shell.nav_support')</span></a>
            </li>
            <li class="nav-item" data-section="system"><a href="#"><x-svg-icon name="person-plus" class="vc-ico" /><span class="menu-title">@lang('shell.nav_admissions')</span></a></li>

            {{-- === Certificates admin link (#192 §9 / #172) === --}}
            @if($sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin()))
            <li class="nav-item {{ request()->routeIs('admin.certificates.*') ? 'active' : '' }}" data-section="system">
                <a href="{{ Route::has('admin.certificates.index') ? route('admin.certificates.index') : '#' }}">
                    <x-svg-icon name="award" class="vc-ico" />
                    <span class="menu-title">@lang('certificates.title')</span>
                </a>
            </li>
            @endif
            @endif

            {{-- === Certificates: teacher / student / parent read link (#192 §9 / #172) === --}}
            @if($sidebarUser && ($sidebarUser->isTeacher() || $sidebarUser->isStudent() || $sidebarUser->isParent()))
            <li class="nav-item {{ request()->routeIs('my.certificates.*') ? 'active' : '' }}">
                <a href="{{ Route::has('my.certificates.index') ? route('my.certificates.index') : '#' }}">
                    <x-svg-icon name="award" class="vc-ico" />
                    <span class="menu-title">@lang('certificates.my_title')</span>
                </a>
            </li>
            @endif

            {{-- === School Calendar card #196 / #174 / #179 / #186 === --}}
            @php
                $scCalIsStaff   = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin() || $sidebarUser->isTeacher());
                $scCalRoute     = $scCalIsStaff
                    ? (Route::has('manage.school-calendar.index') ? route('manage.school-calendar.index') : '#')
                    : (Route::has('my.calendar.index') ? route('my.calendar.index') : '#');
                $scCalActive    = request()->routeIs('manage.school-calendar.*') || request()->routeIs('my.calendar.*');
            @endphp
            <li class="nav-item {{ $scCalActive ? 'active' : '' }}" data-section="educational">
                <a href="{{ $scCalRoute }}"><x-svg-icon name="calendar-week" class="vc-ico" /><span class="menu-title">@lang('shell.nav_calendar')</span></a>
            </li>

            {{-- My education policies — visible to every signed-in user (card #105); distinct
                 label from the admin management page to avoid the duplicate-name confusion (card #122) --}}
            <li class="nav-item {{ request()->routeIs('policies.my.*') ? 'active' : '' }}">
                <a href="{{ Route::has('policies.my.index') ? route('policies.my.index') : '#' }}"><x-svg-icon name="hammer" class="vc-ico" /><span class="menu-title">@lang('shell.nav_my_policies')</span></a>
            </li>

            {{-- Parent canteen controls — visible to parents (card #116) --}}
            @if($sidebarUser && $sidebarUser->hasRole('parent'))
            <li class="nav-item {{ request()->routeIs('my.canteen.*') ? 'active' : '' }}">
                <a href="{{ Route::has('my.canteen.index') ? route('my.canteen.index') : '#' }}"><x-svg-icon name="cup-hot" class="vc-ico" /><span class="menu-title">@lang('canteen.parent.title')</span></a>
            </li>
            @endif

            @if($sidebarUser && $sidebarUser->isTeacher())
                <li class="navigation-header sec-educational"><span>@lang('shell.portal_my_schedule')</span></li>
                <li class="nav-item {{ request()->routeIs('teacher.schedule') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.schedule') }}"><x-svg-icon name="calendar-check" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_schedule_link')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.weekly-plans.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.weekly-plans.index') }}"><x-svg-icon name="list-ul" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_weekly_plans')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.exams.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.exams.index') }}"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_exams')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.grades.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.grades.index') }}"><x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-title">@lang('shell.portal_enter_grades')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.attendance.index') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.attendance.index') }}"><x-svg-icon name="check-square" class="vc-ico" /><span class="menu-title">@lang('shell.portal_record_attendance')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('admin.assignments.index') ? route('admin.assignments.index') : '#' }}"><x-svg-icon name="list-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_assignments')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.my-evaluations.*') || request()->routeIs('admin.evaluations.subjects') || request()->routeIs('admin.evaluations.execute.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('admin.my-evaluations.index') ? route('admin.my-evaluations.index') : '#' }}"><x-svg-icon name="star" class="vc-ico" /><span class="menu-title">@lang('shell.nav_evaluations')</span></a>
                </li>
                {{-- === Question Banks read access — cards #189/#190 === --}}
                <li class="nav-item {{ request()->routeIs('admin.question-banks.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('admin.question-banks.index') ? route('admin.question-banks.index') : '#' }}"><x-svg-icon name="database" class="vc-ico" /><span class="menu-title">@lang('shell.nav_questions_bank')</span></a>
                </li>
                {{-- === Books read access — cards #189/#190 === --}}
                <li class="nav-item {{ request()->routeIs('manage.books.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('manage.books.index') ? route('manage.books.index') : '#' }}"><x-svg-icon name="book" class="vc-ico" /><span class="menu-title">@lang('shell.nav_books')</span></a>
                </li>
                {{-- === Libraries read access — cards #189/#190 === --}}
                <li class="nav-item {{ request()->routeIs('admin.libraries.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('admin.libraries.public.index') ? route('admin.libraries.public.index') : '#' }}"><x-svg-icon name="bookmark" class="vc-ico" /><span class="menu-title">@lang('shell.nav_libraries')</span></a>
                </li>
                {{-- === Subjects read access — cards #189/#190 === --}}
                <li class="nav-item {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('admin.subjects.index') ? route('admin.subjects.index') : '#' }}"><x-svg-icon name="book-half" class="vc-ico" /><span class="menu-title">@lang('shell.nav_subjects')</span></a>
                </li>
                {{-- === My Students — cards #191/#198 === --}}
                <li class="nav-item {{ request()->routeIs('teacher.students.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ route('teacher.students.index') }}"><x-svg-icon name="people" class="vc-ico" /><span class="menu-title">@lang('teacher_students.sidebar_link')</span></a>
                </li>
            @endif

            @if($sidebarUser && $sidebarUser->isStudent())
                <li class="navigation-header sec-educational"><span>@lang('shell.portal_student')</span></li>
                <li class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.dashboard') }}"><x-svg-icon name="house" class="vc-ico" /><span class="menu-title">@lang('shell.portal_dashboard')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.schedule') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.schedule') }}"><x-svg-icon name="calendar-check" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_schedule_link')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.exams') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.exams') }}"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-title">@lang('shell.portal_exams')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.grades') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.grades') }}"><x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_grades')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.attendance') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.attendance') }}"><x-svg-icon name="check-square" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_attendance')</span></a></li>
                {{-- === Books card 65 === --}}<li class="nav-item {{ request()->routeIs('student.books.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('student.books.index') ? route('student.books.index') : '#' }}"><x-svg-icon name="book-half" class="vc-ico" /><span class="menu-title">@lang('shell.nav_books')</span></a></li>
                {{-- === Student subjects cards + content hub — card #171 === --}}<li class="nav-item {{ request()->routeIs('student.subjects.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('student.subjects.index') ? route('student.subjects.index') : '#' }}"><x-svg-icon name="grid" class="vc-ico" /><span class="menu-title">موادي</span></a></li>
                {{-- === Student special-education view — card #173 === --}}<li class="nav-item {{ request()->routeIs('student.special-education') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('student.special-education') ? route('student.special-education') : '#' }}"><x-svg-icon name="heart" class="vc-ico" /><span class="menu-title">@lang('student.special_ed.title')</span></a></li>
                {{-- === Appointments Phase 2 — student === --}}
                <li class="nav-item {{ request()->routeIs('my.appointments.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('my.appointments.index') ? route('my.appointments.index') : '#' }}"><x-svg-icon name="calendar-plus" class="vc-ico" /><span class="menu-title">@lang('shell.nav_my_appointments_booking')</span></a>
                </li>
                {{-- === Student reports & portfolio — card #172 === --}}
                <li class="nav-item {{ request()->routeIs('student.reports.*') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.reports.index') }}"><x-svg-icon name="pie-chart" class="vc-ico" /><span class="menu-title">تقارير الغياب</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.portfolio') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.portfolio') }}"><x-svg-icon name="trophy" class="vc-ico" /><span class="menu-title">ملف الإنجاز</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.reports.exam-schedule') ? 'active' : '' }}" data-section="educational"><a href="{{ route('student.reports.exam-schedule') }}"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-title">جدول الاختبارات</span></a></li>
            @endif

            @if($sidebarUser && $sidebarUser->isParent())
                <li class="navigation-header sec-communication"><span>@lang('shell.portal_parent')</span></li>
                <li class="nav-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}" data-section="communication"><a href="{{ route('parent.dashboard') }}"><x-svg-icon name="house" class="vc-ico" /><span class="menu-title">@lang('shell.portal_dashboard')</span></a></li>
                <li class="nav-item {{ request()->routeIs('parent.contact-teacher') ? 'active' : '' }}" data-section="communication"><a href="{{ route('parent.contact-teacher') }}"><x-svg-icon name="envelope" class="vc-ico" /><span class="menu-title">@lang('shell.portal_contact_teacher')</span></a></li>
                {{-- === Appointments Phase 2 — parent === --}}
                <li class="nav-item {{ request()->routeIs('my.appointments.*') ? 'active' : '' }}" data-section="communication">
                    <a href="{{ Route::has('my.appointments.index') ? route('my.appointments.index') : '#' }}"><x-svg-icon name="calendar-plus" class="vc-ico" /><span class="menu-title">@lang('shell.nav_my_appointments_booking')</span></a>
                </li>
                {{-- === Libraries — card #182 === --}}
                <li class="nav-item {{ request()->routeIs('my.libraries.*') ? 'active' : '' }}" data-section="communication">
                    <a href="{{ Route::has('my.libraries.index') ? route('my.libraries.index') : '#' }}"><x-svg-icon name="bookmark" class="vc-ico" /><span class="menu-title">@lang('shell.nav_libraries')</span></a>
                </li>
            @endif

        </ul>
    </div>
</div>
