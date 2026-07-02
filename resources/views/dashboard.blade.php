@extends('layouts.app')

@section('title', __('dashboard.page_title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('dashboard.page_title')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('dashboard.breadcrumb_home')</a></li>
                        <li class="breadcrumb-item active">@lang('dashboard.page_title')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-body">
    <!-- Welcome Card -->
    <div class="row">
        <div class="col-12">
            <div class="card" style="border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 4px solid #1e9ff2; background: #f8fbff;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1" style="color: #1a1a2e; font-weight: 700;">@lang('dashboard.welcome'), {{ Auth::user()->name_ar ?? Auth::user()->name_en ?? Auth::user()->name }}</h4>
                            <p class="mb-0 text-muted">@lang('dashboard.welcome_subtitle')</p>
                        </div>
                        <div class="text-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}">
                            <p class="mb-0 text-dark fw-bold"><x-svg-icon name="calendar-event-fill" :size="16" class="ic-info" /> {{ now()->format('Y/m/d') }}</p>
                            <small class="text-muted">{{ now()->locale(app()->getLocale())->dayName }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->isSuperAdmin())
    <!-- Super Admin Stats -->
    <div class="row">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-gold mb-1 mx-auto">
                        <x-svg-icon name="building-fill" :size="24" class="ic-gold" />
                    </div>
                    <h2 class="fw-bolder">{{ $schools_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.schools_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-success mb-1 mx-auto">
                        <x-svg-icon name="diagram-3-fill" :size="24" class="ic-success" />
                    </div>
                    <h2 class="fw-bolder">{{ $sections_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.sections_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-info mb-1 mx-auto">
                        <x-svg-icon name="people-fill" :size="24" class="ic-info" />
                    </div>
                    <h2 class="fw-bolder">{{ $classes_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.classes_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-teal mb-1 mx-auto">
                        <x-svg-icon name="person-workspace" :size="24" class="ic-teal" />
                    </div>
                    <h2 class="fw-bolder">{{ $teachers_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.teachers_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-eval mb-1 mx-auto">
                        <x-svg-icon name="mortarboard-fill" :size="24" class="ic-eval" />
                    </div>
                    <h2 class="fw-bolder">{{ $students_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.students_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-gold mb-1 mx-auto">
                        <x-svg-icon name="journal-bookmark-fill" :size="24" class="ic-gold" />
                    </div>
                    <h2 class="fw-bolder">{{ $subjects_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.subjects_count')</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Schools -->
    @if(isset($recent_schools) && $recent_schools->count())
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@lang('dashboard.latest_schools')</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>@lang('dashboard.school_column')</th>
                                    <th>@lang('dashboard.email_column')</th>
                                    <th>@lang('dashboard.status_column')</th>
                                    <th>@lang('dashboard.created_at_column')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_schools as $school)
                                <tr>
                                    <td>{{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar ?: $school->name) : ($school->name_ar ?: $school->name) }}</td>
                                    <td>{{ $school->email }}</td>
                                    <td>
                                        <span class="badge bg-{{ $school->is_active ? 'success' : 'secondary' }}">
                                            {{ $school->is_active ? __('dashboard.status_active') : __('dashboard.status_inactive') }}
                                        </span>
                                    </td>
                                    <td>{{ $school->created_at->format('Y/m/d') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    @if(Auth::user()->isSchoolAdmin())
    <!-- School Admin Stats -->
    <div class="row">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-success mb-1 mx-auto">
                        <x-svg-icon name="diagram-3-fill" :size="24" class="ic-success" />
                    </div>
                    <h2 class="fw-bolder">{{ $sections_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.sections_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-info mb-1 mx-auto">
                        <x-svg-icon name="people-fill" :size="24" class="ic-info" />
                    </div>
                    <h2 class="fw-bolder">{{ $classes_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.classes_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-teal mb-1 mx-auto">
                        <x-svg-icon name="person-workspace" :size="24" class="ic-teal" />
                    </div>
                    <h2 class="fw-bolder">{{ $teachers_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.teachers_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-eval mb-1 mx-auto">
                        <x-svg-icon name="mortarboard-fill" :size="24" class="ic-eval" />
                    </div>
                    <h2 class="fw-bolder">{{ $students_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.students_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-gold mb-1 mx-auto">
                        <x-svg-icon name="journal-bookmark-fill" :size="24" class="ic-gold" />
                    </div>
                    <h2 class="fw-bolder">{{ $subjects_count ?? 0 }}</h2>
                    <p class="card-text">@lang('dashboard.subjects_count')</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card ds-stat text-center">
                <div class="card-body">
                    <div class="ic-chip ic-chip-warn mb-1 mx-auto" style="background:var(--status-warning-bg);color:var(--status-warning);">
                        <x-svg-icon name="list-task" :size="24" class="ic-warn" />
                    </div>
                    <h2 class="fw-bolder">{{ $pending_assignments ?? 0 }}</h2>
                    <p class="card-text">واجبات نشطة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Attendance -->
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">حضور اليوم</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>الحاضرون</span>
                        <span class="text-success fw-bold">{{ $present_today ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>الغائبون</span>
                        <span class="text-danger fw-bold">{{ $absent_today ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>نسبة الحضور</span>
                        <span class="text-primary fw-bold">{{ $attendance_rate ?? 0 }}%</span>
                    </div>
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ $attendance_rate ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الاختبارات القادمة</h4>
                </div>
                <div class="card-body">
                    @if(isset($upcoming_exams) && $upcoming_exams->count())
                        @foreach($upcoming_exams->take(3) as $exam)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>{{ $exam->title }}</strong>
                                <br><small class="text-muted">{{ $exam->subject->name ?? '' }}</small>
                            </div>
                            <span class="badge bg-light-primary">{{ optional($exam->start_time)->format('m/d') }}</span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center mb-0">لا توجد اختبارات قادمة</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">إحصائيات سريعة</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span><x-svg-icon name="calendar-check" :size="16" class="ic-info me-1" />الخطط الأسبوعية</span>
                        <span class="badge bg-info">{{ $weekly_plans_count ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span><x-svg-icon name="list-task" :size="16" class="ic-warn me-1" />الواجبات النشطة</span>
                        <span class="badge bg-warning">{{ $pending_assignments ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><x-svg-icon name="file-earmark-text-fill" :size="16" class="ic-eval me-1" />الاختبارات القادمة</span>
                        <span class="badge bg-primary">{{ isset($upcoming_exams) ? $upcoming_exams->count() : 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Grades -->
    @if(isset($recent_grades) && $recent_grades->count())
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">آخر الدرجات المسجلة</h4>
                    <a href="{{ route('admin.grades.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th>المادة</th>
                                    <th>الاختبار</th>
                                    <th>الدرجة</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_grades as $grade)
                                <tr>
                                    <td>{{ $grade->student->name ?? '-' }}</td>
                                    <td>{{ $grade->subject->name ?? '-' }}</td>
                                    <td>{{ $grade->exam->title ?? '-' }}</td>
                                    <td>{{ $grade->score }}/{{ $grade->max_score }}</td>
                                    <td>
                                        <span class="badge bg-{{ $grade->percentage >= 60 ? 'success' : 'danger' }}">
                                            {{ number_format($grade->percentage, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    @if(Auth::user()->isTeacher())
    {{-- Card #294 — رئيسية المعلم: summary grid, each card = count + quick link. --}}
    @php
        $teacherCards = [
            ['label' => 'مواد المعلم', 'sub' => 'مادة أدرّسها', 'value' => $subjects_count ?? 0, 'icon' => 'journal-bookmark-fill', 'chip' => 'gold', 'route' => 'admin.subjects.index'],
            ['label' => 'المكتبات', 'sub' => 'عنصر بالمكتبة', 'value' => $library_items_count ?? 0, 'icon' => 'collection-fill', 'chip' => 'teal', 'route' => 'admin.libraries.public.index'],
            ['label' => 'صندوق البريد', 'sub' => 'رسالة غير مقروءة', 'value' => $mailbox_unread_count ?? 0, 'icon' => 'envelope-fill', 'chip' => 'info', 'route' => 'my.mailbox.index'],
            ['label' => 'الفصول الافتراضية', 'sub' => 'جلسة قادمة', 'value' => $virtual_classes_upcoming_count ?? 0, 'icon' => 'camera-video-fill', 'chip' => 'navy', 'route' => 'manage.virtual-classes.index'],
            ['label' => 'غرف النقاش', 'sub' => 'غرفة نشطة', 'value' => $discussion_rooms_count ?? 0, 'icon' => 'chat-square-text-fill', 'chip' => 'eval', 'route' => 'manage.discussion-rooms.index'],
            ['label' => 'تسليمات تحتاج للتصحيح', 'sub' => 'بانتظار التصحيح', 'value' => $submissions_to_grade_count ?? 0, 'icon' => 'pencil-square', 'chip' => 'danger', 'route' => 'admin.assignments.index'],
            ['label' => 'الواجبات', 'sub' => 'واجب منشور', 'value' => $assignments_published_count ?? 0, 'icon' => 'list-check', 'chip' => 'success', 'route' => 'admin.assignments.index'],
            ['label' => 'الاختبارات', 'sub' => 'اختبار قادم', 'value' => isset($upcoming_exams) ? $upcoming_exams->count() : 0, 'icon' => 'file-earmark-text-fill', 'chip' => 'gold', 'route' => 'teacher.exams.index'],
            ['label' => 'الخطة الأسبوعية', 'sub' => 'خطة قادمة', 'value' => isset($weekly_plans) ? $weekly_plans->count() : 0, 'icon' => 'calendar-week', 'chip' => 'info', 'route' => 'teacher.weekly-plans.index'],
            ['label' => 'الجدول المدرسي', 'sub' => 'حصة اليوم', 'value' => isset($today_schedules) ? $today_schedules->count() : 0, 'icon' => 'calendar3', 'chip' => 'navy', 'route' => 'teacher.schedule'],
            ['label' => 'الدعم الفني', 'sub' => 'تذكرة مفتوحة', 'value' => $support_tickets_open_count ?? 0, 'icon' => 'life-preserver', 'chip' => 'teal', 'route' => 'my.support.index'],
        ];
    @endphp
    <div class="row">
        @foreach($teacherCards as $card)
        <div class="col-xl-3 col-lg-4 col-md-6 mb-2">
            <a href="{{ route($card['route']) }}" class="text-decoration-none">
                <div class="card ds-stat ds-hover-card text-center h-100 mb-0">
                    <div class="card-body">
                        <div class="ic-chip ic-chip-{{ $card['chip'] }} mb-1 mx-auto">
                            <x-svg-icon :name="$card['icon']" :size="24" :class="'ic-'.$card['chip']" />
                        </div>
                        <h2 class="fw-bolder text-dark">{{ $card['value'] }}</h2>
                        <p class="card-text mb-0 text-dark">{{ $card['label'] }}</p>
                        <small class="text-muted">{{ $card['sub'] }}</small>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    <!-- Teacher's Schedule Today -->
    @if(isset($today_schedules) && $today_schedules->count())
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">جدول اليوم</h4>
                </div>
                <div class="card-body">
                    @foreach($today_schedules as $schedule)
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div>
                            <strong>{{ $schedule->subject->name ?? '' }}</strong>
                            <br><small class="text-muted">{{ $schedule->classRoom->name ?? '' }}</small>
                        </div>
                        <span class="badge bg-primary">{{ $schedule->periods->first()->start_time ?? '' }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الواجبات</h4>
                </div>
                <div class="card-body">
                    @if(isset($assignments) && $assignments->count())
                        @foreach($assignments as $assignment)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>{{ $assignment->title }}</strong>
                                <br><small class="text-muted">{{ $assignment->submissions_count }} تسليم</small>
                            </div>
                            <span class="badge bg-{{ $assignment->due_date->isPast() ? 'danger' : 'warning' }}">
                                {{ $assignment->due_date->format('m/d') }}
                            </span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center mb-0">لا توجد واجبات</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    @if(Auth::user()->isStudent() || Auth::user()->isParent())
    <!-- Student/Parent View -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <x-svg-icon name="calendar-event-fill" :size="56" class="ic-info mb-2" />
                    <h4>سيتم إضافة المزيد من المعلومات قريباً</h4>
                    <p class="text-muted">يمكنك متابعة الجدول الدراسي والدرجات والحضور من هنا</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(Auth::user()->isSuperAdmin() || Auth::user()->isSchoolAdmin())

    {{-- ============== Section 2 — Interaction rates ============== --}}
    @php
        $ir = $interactionRates ?? [];
        $irRows = [
            ['label' => __('dashboard.students_login_rate'), 'key' => 'studentsLoginRate', 'color' => 'primary'],
            ['label' => __('dashboard.teachers_login_rate'), 'key' => 'teachersLoginRate', 'color' => 'info'],
            ['label' => __('dashboard.parents_login_rate'), 'key' => 'parentsLoginRate', 'color' => 'warning'],
            ['label' => __('dashboard.student_teacher_interaction'), 'key' => 'studentTeacherInteraction', 'color' => 'success'],
            ['label' => __('dashboard.student_content_interaction'), 'key' => 'studentContentInteraction', 'color' => 'danger'],
        ];
    @endphp
    <div class="row">
        <div class="col-12">
            <div class="card" id="dashboard-section-interaction-rates">
                <div class="card-header"><h4 class="card-title">@lang('dashboard.login_rates')</h4></div>
                <div class="card-body">
                    @foreach($irRows as $row)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>{{ $row['label'] }}</span>
                                <span class="text-{{ $row['color'] }} fw-bold">{{ $ir[$row['key']] ?? 0 }}%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-{{ $row['color'] }}" role="progressbar" style="width: {{ $ir[$row['key']] ?? 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ============== Section 3 — Content stats ============== --}}
    @php
        $cs = $contentStatsData ?? [];
        $csTiles = [
            ['label' => __('dashboard.electronic_exams'), 'key' => 'electronicExams', 'icon' => 'la-file-text', 'color' => 'primary'],
            ['label' => __('dashboard.electronic_assignments'), 'key' => 'electronicAssignments', 'icon' => 'la-tasks', 'color' => 'info'],
            ['label' => __('dashboard.videos_files'), 'key' => 'videosFiles', 'icon' => 'la-video-camera', 'color' => 'warning'],
            ['label' => __('dashboard.content_interaction_rate'), 'key' => 'contentInteractionRate', 'icon' => 'la-heart', 'color' => 'danger', 'suffix' => '%'],
            ['label' => __('dashboard.view_rate'), 'key' => 'viewRate', 'icon' => 'la-eye', 'color' => 'success', 'suffix' => '%'],
            ['label' => __('dashboard.content_interactions'), 'key' => 'contentInteractions', 'icon' => 'la-comments', 'color' => 'secondary'],
            ['label' => __('dashboard.exam_submissions'), 'key' => 'examSubmissions', 'icon' => 'la-check', 'color' => 'primary'],
            ['label' => __('dashboard.assignment_submissions'), 'key' => 'assignmentSubmissions', 'icon' => 'la-check-square', 'color' => 'info'],
            ['label' => __('dashboard.sms_usage'), 'key' => 'smsUsage', 'icon' => 'la-mobile-phone', 'color' => 'warning'],
        ];
    @endphp
    <div class="row" id="dashboard-section-content-stats">
        @foreach($csTiles as $tile)
            <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="avatar bg-light-{{ $tile['color'] }} p-50 mb-1">
                            <div class="avatar-content"><i class="la {{ $tile['icon'] }} la-2x"></i></div>
                        </div>
                        <h2 class="fw-bolder">{{ $cs[$tile['key']] ?? 0 }}{{ $tile['suffix'] ?? '' }}</h2>
                        <p class="card-text">{{ $tile['label'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ============== Section 4 — Various stats ============== --}}
    @php
        $vs = $variousStatsData ?? [];
        $vsTiles = [
            ['label' => __('dashboard.discussion_rooms'), 'key' => 'discussionRooms', 'icon' => 'la-comments', 'color' => 'primary'],
            ['label' => __('dashboard.absences'), 'key' => 'absences', 'icon' => 'la-user-times', 'color' => 'danger'],
            ['label' => __('dashboard.preparation_plans'), 'key' => 'preparationPlans', 'icon' => 'la-list-alt', 'color' => 'info'],
            ['label' => __('dashboard.questions_count'), 'key' => 'questionsCount', 'icon' => 'la-question-circle', 'color' => 'warning'],
            ['label' => __('dashboard.virtual_classes'), 'key' => 'virtualClasses', 'icon' => 'la-video-camera', 'color' => 'success'],
            ['label' => __('dashboard.scheduled_virtual_classes'), 'key' => 'scheduledVirtualClasses', 'icon' => 'la-calendar', 'color' => 'secondary'],
        ];
    @endphp
    <div class="row" id="dashboard-section-various-stats">
        @foreach($vsTiles as $tile)
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="avatar bg-light-{{ $tile['color'] }} p-50 mb-1">
                            <div class="avatar-content"><i class="la {{ $tile['icon'] }} la-2x"></i></div>
                        </div>
                        <h3 class="fw-bolder">{{ $vs[$tile['key']] ?? 0 }}</h3>
                        <p class="card-text">{{ $tile['label'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ============== Section 5 — Weekly absence ============== --}}
    <div class="row">
        <div class="col-12">
            <div class="card" id="dashboard-section-weekly-absence">
                <div class="card-header"><h4 class="card-title">@lang('dashboard.weekly_absence_rate')</h4></div>
                <div class="card-body">
                    <div style="position:relative; min-height:200px;"><canvas id="weeklyAbsenceChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============== Section 6 — Most active ============== --}}
    @php $ma = $mostActive ?? []; @endphp
    <div class="row" id="dashboard-section-most-active">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header"><h4 class="card-title">@lang('dashboard.active_classes_in_school')</h4></div>
                <div class="card-body">
                    @forelse($ma['activeClassesInSchool'] ?? [] as $row)
                        <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] ?? '-' }}</span><span class="badge bg-primary">{{ $row['score'] ?? 0 }}</span></div>
                    @empty
                        <p class="text-muted text-center mb-0">@lang('dashboard.no_data_yet')</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header"><h4 class="card-title">@lang('dashboard.active_users_in_school')</h4></div>
                <div class="card-body">
                    @forelse($ma['activeUsersInSchool'] ?? [] as $row)
                        <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] ?? '-' }}</span><span class="badge bg-info">{{ $row['score'] ?? 0 }}</span></div>
                    @empty
                        <p class="text-muted text-center mb-0">@lang('dashboard.no_data_yet')</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header"><h4 class="card-title">@lang('dashboard.active_classes_in_company')</h4></div>
                <div class="card-body">
                    @forelse($ma['activeClassesInCompany'] ?? [] as $row)
                        <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] ?? '-' }}</span><span class="badge bg-warning">{{ $row['score'] ?? 0 }}</span></div>
                    @empty
                        <p class="text-muted text-center mb-0">@lang('dashboard.no_data_yet')</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header"><h4 class="card-title">@lang('dashboard.active_users_in_company')</h4></div>
                <div class="card-body">
                    @forelse($ma['activeUsersInCompany'] ?? [] as $row)
                        <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] ?? '-' }}</span><span class="badge bg-success">{{ $row['score'] ?? 0 }}</span></div>
                    @empty
                        <p class="text-muted text-center mb-0">@lang('dashboard.no_data_yet')</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ============== Section 7 — Weekly activity ============== --}}
    <div class="row">
        <div class="col-12">
            <div class="card" id="dashboard-section-weekly-activity">
                <div class="card-header"><h4 class="card-title">@lang('dashboard.section_weekly_activity')</h4></div>
                <div class="card-body">
                    <div style="position:relative; min-height:200px;"><canvas id="weeklyActivityChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    @php
        $chartI18n = [
            'days' => [
                'sat' => __('dashboard.day_sat'),
                'sun' => __('dashboard.day_sun'),
                'mon' => __('dashboard.day_mon'),
                'tue' => __('dashboard.day_tue'),
                'wed' => __('dashboard.day_wed'),
                'thu' => __('dashboard.day_thu'),
                'fri' => __('dashboard.day_fri'),
            ],
            'absenceLabel' => __('dashboard.absence_rate_label'),
            'parents' => __('dashboard.series_parents'),
            'students' => __('dashboard.series_students'),
            'teachers' => __('dashboard.series_teachers'),
        ];
    @endphp
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const weeklyAbsence = @json($weeklyAbsence ?? []);
            const weeklyActivity = @json($weeklyActivity ?? []);
            const i18n = @json($chartI18n);
            const dayLabel = (d) => i18n.days[d] || d;

            const absCtx = document.getElementById('weeklyAbsenceChart');
            if (absCtx && window.Chart) {
                new Chart(absCtx, {
                    type: 'bar',
                    data: {
                        labels: weeklyAbsence.map(r => dayLabel(r.day)),
                        datasets: [{ label: i18n.absenceLabel, data: weeklyAbsence.map(r => r.rate || 0), backgroundColor: 'rgba(220,53,69,.6)' }]
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
                });
            }

            const actCtx = document.getElementById('weeklyActivityChart');
            const series = weeklyActivity.series || [];
            if (actCtx && window.Chart) {
                new Chart(actCtx, {
                    type: 'line',
                    data: {
                        labels: series.map(r => dayLabel(r.day)),
                        datasets: [
                            { label: i18n.parents, data: series.map(r => r.parents || 0), borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,.2)' },
                            { label: i18n.students, data: series.map(r => r.students || 0), borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.2)' },
                            { label: i18n.teachers, data: series.map(r => r.teachers || 0), borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.2)' },
                        ]
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
                });
            }
        })();
    </script>
    @endpush

    @endif

</div>
@endsection
