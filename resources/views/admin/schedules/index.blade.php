@extends('layouts.app')

@section('title', __('schedule.page_title'))
@section('body_class', 'theme-light')

@push('styles')
<style>
    .schedule-toolbar { gap: .5rem; }
    .schedule-status-pills { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
    .schedule-status-pills .pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .65rem; border-radius: 999px;
        font-size: .75rem; line-height: 1.2;
        background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
        text-decoration: none; transition: filter .15s ease, box-shadow .15s ease;
        cursor: pointer;
    }
    .schedule-status-pills a.pill:hover,
    .schedule-status-pills a.pill:focus {
        text-decoration: none;
        filter: brightness(.94);
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        outline: none;
    }
    .schedule-status-pills .pill.on { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .schedule-status-pills .pill.off { background: #fff7ed; color: #92400e; border-color: #fed7aa; }

    .view-toggle .btn { border-radius: 999px; padding: .35rem 1rem; }

    .week-grid {
        width: 100%; border-collapse: separate; border-spacing: 0;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;
    }
    .week-grid th, .week-grid td {
        border-bottom: 1px solid #eef2f7;
        border-inline-end: 1px solid #eef2f7;
        padding: .5rem; vertical-align: top; text-align: center;
    }
    .week-grid th { background: #f8fafc; color: #334155; font-weight: 600; font-size: .85rem; }
    .week-grid .period-col { width: 70px; background: #f8fafc; font-weight: 600; color: #475569; }
    .week-grid tr:last-child td, .week-grid tr:last-child th { border-bottom: 0; }
    .week-grid th:last-child, .week-grid td:last-child { border-inline-end: 0; }

    .period-card {
        display: block; padding: .5rem .55rem; border-radius: 8px;
        background: #eff6ff; color: #1e3a8a; border: 1px solid #dbeafe;
        text-align: start; margin-bottom: .25rem;
    }
    .period-card:last-child { margin-bottom: 0; }
    .period-card .subject { font-weight: 700; font-size: .82rem; }
    .period-card .meta { font-size: .72rem; color: #475569; margin-top: .15rem; }
    .period-card .room { font-size: .68rem; color: #1e40af; }

    .empty-cell { color: #cbd5e1; font-size: .85rem; }

    .schedule-group {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        margin-bottom: 1rem; overflow: hidden;
    }
    .schedule-group-header {
        padding: .85rem 1rem; border-bottom: 1px solid #eef2f7;
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: .5rem; background: #fafbff;
    }
    .schedule-group-header .group-title { font-weight: 700; color: #0f172a; }
    .schedule-group-header .group-sub { font-size: .78rem; color: #64748b; }
    .schedule-group-meta { display: flex; gap: .75rem; flex-wrap: wrap; font-size: .75rem; color: #475569; }
    .schedule-group-meta .meta-chip {
        background: #f1f5f9; border-radius: 999px; padding: .15rem .6rem;
    }

    .summary-tiles { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-bottom: 1rem; }
    .summary-tile {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: .75rem .9rem;
    }
    .summary-tile .label { font-size: .75rem; color: #64748b; }
    .summary-tile .value { font-size: 1.35rem; font-weight: 700; color: #0f172a; }

    /* ============================================================
       Print stylesheet — show ONLY the .week-grid(s) at full
       page width. Everything else (sidebar, navbar, filters,
       summary tiles, action buttons) is hidden.
       ============================================================ */
    @media print {
        /* ---- Page setup ---- */
        @page { size: A4 landscape; margin: 1.2cm 1cm; }

        /* ---- Hide the entire chrome ---- */
        .main-menu,
        .header-navbar,
        footer.footer,
        .content-header,
        .no-print,
        .breadcrumb-wrapper,
        .schedule-toolbar,
        .schedule-status-pills,
        .summary-tiles,
        .view-toggle,
        .card.no-print,
        .card-header,
        .card-body .row,
        .alert,
        [data-feather] { display: none !important; }

        /* ---- Reset layout so grid fills the page ---- */
        body,
        html { background: #fff !important; margin: 0 !important; padding: 0 !important; }

        .app-content.content,
        .content-wrapper,
        .content-body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

        /* ---- Print header (hidden on screen, shown in print) ---- */
        .print-header { display: block !important; margin-bottom: .6cm; }
        .print-header h2 { font-size: 14pt; font-weight: 700; margin: 0 0 2pt; }
        .print-header .print-meta { font-size: 9pt; color: #475569; }

        /* ---- Schedule groups ---- */
        .schedule-group {
            break-inside: avoid;
            border: 1px solid #ccc !important;
            border-radius: 0 !important;
            margin-bottom: .5cm !important;
            overflow: visible !important;
        }

        /* Keep the group header (shows teacher/class + academic year) */
        .schedule-group-header {
            background: #f0f4f8 !important;
            padding: 4pt 6pt !important;
            print-color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
        }

        /* Hide action links inside group header (view-grid / edit-periods) */
        .schedule-group-meta a.meta-chip { display: none !important; }

        /* ---- Make grid expand to full page width ---- */
        .table-responsive { overflow: visible !important; width: 100% !important; }

        .week-grid {
            width: 100% !important;
            border: 1px solid #ccc !important;
            border-radius: 0 !important;
            border-collapse: collapse !important;
            print-color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
        }

        .week-grid th,
        .week-grid td {
            border: 1px solid #d1d5db !important;
            padding: 3pt 4pt !important;
            break-inside: avoid !important;
            print-color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
        }

        .week-grid th {
            background: #f8fafc !important;
            print-color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
        }

        .week-grid tr { break-inside: avoid !important; }

        /* Period cards — keep colour for readability */
        .period-card {
            break-inside: avoid !important;
            print-color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
            background: #eff6ff !important;
            border: 1px solid #dbeafe !important;
            margin-bottom: 2pt !important;
            padding: 2pt 4pt !important;
            border-radius: 4pt !important;
        }
    }

    /* Print header is invisible on screen */
    .print-header { display: none; }

    @media (max-width: 575.98px) {
        .week-grid { font-size: .72rem; }
        .week-grid .period-col { width: 50px; }
        .week-grid th, .week-grid td { padding: .35rem .25rem; }
        .period-card { padding: .35rem; }
        .period-card .subject { font-size: .75rem; }
    }
</style>
@endpush

@section('content')
@php
    $view = $view ?? 'class';
    $filters = $filters ?? [];
    $days = $days ?? [];
    $periodsCount = $periodsCount ?? 7;
@endphp

<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('schedule.page_title')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item active">@lang('schedule.breadcrumb')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12 d-flex flex-wrap schedule-toolbar justify-content-md-end no-print">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
            <i data-feather="printer"></i> @lang('schedule.print_pdf')
        </button>
        <a href="{{ route('manage.schedules.list') }}" class="btn btn-outline-primary">
            <i data-feather="list"></i> @lang('schedule.manage_schedules')
        </a>
        <a href="{{ route('manage.schedules.create') }}" class="btn btn-primary">
            <i data-feather="plus"></i> @lang('schedule.create_schedule')
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card no-print">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="card-title">@lang('schedule.filters')</h4>
            <div class="view-toggle btn-group" role="group">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'class']) }}"
                   class="btn btn-sm {{ $view === 'class' ? 'btn-primary' : 'btn-outline-primary' }}">
                    @lang('schedule.view_by_class')
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'teacher']) }}"
                   class="btn btn-sm {{ $view === 'teacher' ? 'btn-primary' : 'btn-outline-primary' }}">
                    @lang('schedule.view_by_teacher')
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2">
                <input type="hidden" name="view" value="{{ $view }}">
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.section')</label>
                    <select name="section_id" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ ($filters['section_id'] ?? null) == $section->id ? 'selected' : '' }}>
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.class')</label>
                    <select name="class_id" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ ($filters['class_id'] ?? null) == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}{{ $class->division ? ' - ' . $class->division : '' }}
                                @if($class->section) ({{ $class->section->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.teacher')</label>
                    <select name="teacher_id" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ ($filters['teacher_id'] ?? null) == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.subject')</label>
                    <select name="subject_id" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ ($filters['subject_id'] ?? null) == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.student')</label>
                    <select name="student_id" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ ($filters['student_id'] ?? null) == $student->id ? 'selected' : '' }}>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.academic_year')</label>
                    <select name="academic_year_id" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ ($filters['academic_year_id'] ?? null) == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.semester')</label>
                    <select name="semester" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        <option value="first" {{ ($filters['semester'] ?? null) === 'first' ? 'selected' : '' }}>الفصل الأول</option>
                        <option value="second" {{ ($filters['semester'] ?? null) === 'second' ? 'selected' : '' }}>الفصل الثاني</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-1">@lang('schedule.search')</button>
                    <a href="{{ route('manage.schedules.index') }}" class="btn btn-outline-secondary">@lang('schedule.reset')</a>
                </div>
            </form>

            @php
                // Build query params to forward the current filter scope to linked modules.
                $statusLinkParams = array_filter([
                    'teacher_id'       => $filters['teacher_id'] ?? null,
                    'class_id'         => $filters['class_id'] ?? null,
                    'subject_id'       => $filters['subject_id'] ?? null,
                    'academic_year_id' => $filters['academic_year_id'] ?? null,
                    'semester'         => $filters['semester'] ?? null,
                ]);
            @endphp
            <div class="schedule-status-pills">
                {{-- Weekly Plan status — links to weekly-plans index with current scope --}}
                <a href="{{ route('manage.weekly-plans.index', $statusLinkParams) }}"
                   class="pill {{ ($statusFlags['weekly_plan'] ?? false) ? 'on' : 'off' }}"
                   title="@lang('schedule.weekly_plan_status')">
                    <i data-feather="book-open" style="width:14px;height:14px;"></i>
                    @lang('schedule.weekly_plan_status'):
                    {{ ($statusFlags['weekly_plan'] ?? false) ? __('schedule.linked') : __('schedule.not_linked') }}
                </a>
                {{-- Lesson Prep status — links to lessons index with current scope.
                     NOTE: lesson_prep is currently approximated from weekly_plan presence
                     (no separate lesson-prep table exists). The badge shows the same value
                     as weekly_plan until a dedicated lesson-prep module ships. --}}
                <a href="{{ route('admin.lessons.index', $statusLinkParams) }}"
                   class="pill {{ ($statusFlags['lesson_prep'] ?? false) ? 'on' : 'off' }}"
                   title="@lang('schedule.lesson_prep_status')">
                    <i data-feather="edit-3" style="width:14px;height:14px;"></i>
                    @lang('schedule.lesson_prep_status'):
                    {{ ($statusFlags['lesson_prep'] ?? false) ? __('schedule.linked') : __('schedule.not_linked') }}
                </a>
                {{-- Attendance status — links to attendance index with current scope --}}
                <a href="{{ route('admin.attendance.index', $statusLinkParams) }}"
                   class="pill {{ ($statusFlags['attendance'] ?? false) ? 'on' : 'off' }}"
                   title="@lang('schedule.attendance_status')">
                    <i data-feather="user-check" style="width:14px;height:14px;"></i>
                    @lang('schedule.attendance_status'):
                    {{ ($statusFlags['attendance'] ?? false) ? __('schedule.linked') : __('schedule.not_linked') }}
                </a>
            </div>
        </div>
    </div>

    <div class="summary-tiles">
        <div class="summary-tile">
            <div class="label">@lang('schedule.total_schedules')</div>
            <div class="value">{{ $totals['schedules'] }}</div>
        </div>
        <div class="summary-tile">
            <div class="label">@lang('schedule.total_periods')</div>
            <div class="value">{{ $totals['periods'] }}</div>
        </div>
        <div class="summary-tile">
            <div class="label">@lang('schedule.teacher')</div>
            <div class="value">{{ $totals['teachers'] }}</div>
        </div>
        <div class="summary-tile">
            <div class="label">@lang('schedule.class')</div>
            <div class="value">{{ $totals['classes'] }}</div>
        </div>
    </div>

    {{-- Print-only context header (hidden on screen via CSS) --}}
    <div class="print-header">
        @php
            $printSchool = auth()->user()?->school?->name ?? config('app.name');
            $printDate   = now()->translatedFormat('l، d F Y');
            $printParts  = [];
            if (!empty($filters['teacher_id'])) {
                $t = $teachers->firstWhere('id', $filters['teacher_id']);
                if ($t) $printParts[] = __('schedule.teacher') . ': ' . $t->name;
            }
            if (!empty($filters['class_id'])) {
                $c = $classes->firstWhere('id', $filters['class_id']);
                if ($c) $printParts[] = __('schedule.class') . ': ' . $c->name . ($c->division ? ' - ' . $c->division : '');
            }
            if (!empty($filters['subject_id'])) {
                $s = $subjects->firstWhere('id', $filters['subject_id']);
                if ($s) $printParts[] = __('schedule.subject') . ': ' . $s->name;
            }
            if (!empty($filters['semester'])) {
                $semLabel = $filters['semester'] === 'first' ? 'الفصل الأول' : 'الفصل الثاني';
                $printParts[] = $semLabel;
            }
        @endphp
        <h2>{{ $printSchool }} — {{ __('schedule.page_title') }}</h2>
        <div class="print-meta">
            {{ $printDate }}
            @if($printParts)
                &nbsp;·&nbsp; {{ implode(' &nbsp;·&nbsp; ', $printParts) }}
            @endif
        </div>
    </div>

    @forelse($groupedTimetable as $group)
        <div class="schedule-group">
            <div class="schedule-group-header">
                <div>
                    <div class="group-title">
                        @if($view === 'class')
                            <i data-feather="users" style="width:14px;height:14px;"></i>
                        @else
                            <i data-feather="user" style="width:14px;height:14px;"></i>
                        @endif
                        {{ $group['group_label'] ?: '—' }}
                    </div>
                    @if(!empty($group['sub_label']))
                        <div class="group-sub">{{ $group['sub_label'] }}</div>
                    @endif
                </div>
                <div class="schedule-group-meta">
                    @if(!empty($group['meta']))
                        @if(!empty($group['meta']['academic_year']))
                            <span class="meta-chip">{{ $group['meta']['academic_year'] }}</span>
                        @endif
                        @if(!empty($group['meta']['semester']))
                            <span class="meta-chip">{{ $group['meta']['semester'] }}</span>
                        @endif
                    @endif
                    <span class="meta-chip">@lang('schedule.total_periods'): {{ $group['count'] }}</span>
                    @if($view === 'class' && !empty($group['schedule_id']))
                        <a href="{{ route('manage.schedules.show', $group['schedule_id']) }}" class="meta-chip text-primary">
                            <i data-feather="eye" style="width:12px;height:12px;"></i>
                            @lang('schedule.view_grid_for_class')
                        </a>
                        <a href="{{ route('manage.schedules.edit', $group['schedule_id']) }}" class="meta-chip text-warning">
                            <i data-feather="edit" style="width:12px;height:12px;"></i>
                            @lang('schedule.edit_periods')
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="week-grid">
                    <thead>
                        <tr>
                            <th class="period-col">@lang('schedule.period')</th>
                            @foreach($days as $dayNum => $dayName)
                                <th>{{ $dayName }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for($period = 1; $period <= $periodsCount; $period++)
                            <tr>
                                <th class="period-col">{{ $period }}</th>
                                @foreach($days as $dayNum => $dayName)
                                    <td>
                                        @php $cellPeriods = $group['grid'][$dayNum][$period] ?? []; @endphp
                                        @if(empty($cellPeriods))
                                            <span class="empty-cell">—</span>
                                        @else
                                            @foreach($cellPeriods as $p)
                                                <span class="period-card">
                                                    <span class="subject">{{ optional($p->subject)->name ?: '—' }}</span>
                                                    <span class="meta">
                                                        @if($view === 'teacher')
                                                            @if(optional($p->schedule)->classRoom)
                                                                <i data-feather="users" style="width:11px;height:11px;"></i>
                                                                {{ $p->schedule->classRoom->name }}{{ $p->schedule->classRoom->division ? ' - ' . $p->schedule->classRoom->division : '' }}
                                                            @endif
                                                        @else
                                                            @if(optional($p->teacher)->name)
                                                                <i data-feather="user" style="width:11px;height:11px;"></i>
                                                                {{ $p->teacher->name }}
                                                            @endif
                                                        @endif
                                                    </span>
                                                    @if($p->room)
                                                        <span class="room"><i data-feather="map-pin" style="width:11px;height:11px;"></i> {{ $p->room }}</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i data-feather="calendar" style="width:36px;height:36px;color:#94a3b8;"></i>
                <p class="mt-2 mb-0">@lang('schedule.no_results')</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
