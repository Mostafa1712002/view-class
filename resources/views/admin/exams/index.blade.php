@extends('layouts.app')

@section('title', __('exams_admin.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .exams-kpis .card { padding: 1rem 1.1rem; }
    body.theme-light .exams-kpis .label {
        color: #64748b; font-weight: 600; font-size: .78rem; letter-spacing: .3px;
        text-transform: uppercase; margin-bottom: .35rem;
    }
    body.theme-light .exams-kpis .value {
        font-size: 1.65rem; font-weight: 800; color: var(--gold-400);
        letter-spacing: -.5px; line-height: 1;
    }
    body.theme-light .exams-kpis .icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500); font-size: 1.2rem;
        box-shadow: inset 0 0 0 1px rgba(207,160,70,.18);
    }
    body.theme-light .add-exam-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; padding: .55rem 1rem;
        border-radius: 10px; font-weight: 600; box-shadow: 0 4px 14px rgba(207,160,70,.25);
    }
    body.theme-light .add-exam-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(207,160,70,.32); }
    body.theme-light .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; padding: .5rem .9rem; font-weight: 500;
    }
    body.theme-light .btn-soft:hover { background: #f8fafc; color: #0f172a; }

    body.theme-light .filters-card { border-radius: 16px; }
    body.theme-light .filters-card .form-label {
        font-size: .78rem; color: #64748b; font-weight: 600;
        margin-bottom: .25rem; letter-spacing: .2px;
    }
    body.theme-light .filters-card .form-select,
    body.theme-light .filters-card .form-control {
        border-radius: 10px; border: 1px solid #e5e7eb; background: #fff;
        font-size: .88rem; padding: .45rem .7rem;
    }
    body.theme-light .filters-card .form-select:focus,
    body.theme-light .filters-card .form-control:focus {
        border-color: var(--gold-300); box-shadow: 0 0 0 3px rgba(207,160,70,.12);
    }

    body.theme-light .exam-title { font-weight: 700; color: #0f172a; }
    body.theme-light .exam-meta   { color: #64748b; font-size: .78rem; }
    body.theme-light .type-chip {
        padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        background: #eef2ff; color: #4338ca;
    }
    body.theme-light .type-chip.t-quiz       { background: #eef2ff; color: #4338ca; }
    body.theme-light .type-chip.t-midterm    { background: #fef3c7; color: #92400e; }
    body.theme-light .type-chip.t-final      { background: #fee2e2; color: #b91c1c; }
    body.theme-light .type-chip.t-assignment { background: #ecfdf5; color: #047857; }
    body.theme-light .type-chip.t-homework   { background: #e0f2fe; color: #0369a1; }

    body.theme-light .status-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .15rem .55rem; border-radius: 999px;
        font-size: .72rem; font-weight: 600;
    }
    body.theme-light .status-pill.draft     { background: #f1f5f9; color: #475569; }
    body.theme-light .status-pill.scheduled { background: #e0f2fe; color: #0369a1; }
    body.theme-light .status-pill.active    { background: #ecfdf5; color: #047857; }
    body.theme-light .status-pill.completed { background: #ede9fe; color: #5b21b6; }
    body.theme-light .status-pill.cancelled { background: #fef2f2; color: #b91c1c; }

    body.theme-light .pub-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .12rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 600;
    }
    body.theme-light .pub-pill.on  { background: #ecfdf5; color: #047857; }
    body.theme-light .pub-pill.off { background: #f1f5f9; color: #64748b; }

    body.theme-light .row-actions .btn-icon {
        width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; color: #475569;
    }
    body.theme-light .row-actions .btn-icon:hover { background: #f8fafc; color: #0f172a; }
    body.theme-light .row-actions .dropdown-menu { min-width: 220px; border-radius: 12px; }

    body.theme-light .empty-state { padding: 3.5rem 1rem; text-align: center; }
    body.theme-light .empty-state .icon-wrap {
        width: 72px; height: 72px; border-radius: 18px; margin: 0 auto 1rem;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500); font-size: 1.8rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    body.theme-light .empty-state h5 { color: #0f172a; font-weight: 700; }
    body.theme-light .empty-state p { color: #64748b; max-width: 460px; margin: 0 auto 1.2rem; }

    @media (max-width: 575.98px) {
        body.theme-light .exams-kpis .value { font-size: 1.35rem; }
        body.theme-light .filters-card { padding: .5rem; }
    }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('exams_admin.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('exams_admin.breadcrumb')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.exams.create') }}" class="btn add-exam-btn">
            <x-svg-icon name="plus-lg" :size="16" class="me-1" /> @lang('exams_admin.actions.add')
        </a>
    </div>
</div>

<div class="content-body">
    {{-- Tabs: Class schedule | Exam schedule --}}
    @include('admin.exams._schedule_tabs', ['active' => 'exam'])

    {{-- KPI tiles --}}
    <div class="row exams-kpis mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">@lang('exams_admin.kpis.total')</div>
                        <div class="value">{{ $stats['total'] }}</div>
                    </div>
                    <span class="icon"><x-svg-icon name="file-earmark-text-fill" :size="20" class="ic-eval" /></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">@lang('exams_admin.kpis.published')</div>
                        <div class="value">{{ $stats['published'] }}</div>
                    </div>
                    <span class="icon"><x-svg-icon name="check-circle-fill" :size="20" class="ic-success" /></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">@lang('exams_admin.kpis.active')</div>
                        <div class="value">{{ $stats['active'] }}</div>
                    </div>
                    <span class="icon"><x-svg-icon name="lightning-fill" :size="20" class="ic-warn" /></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">@lang('exams_admin.kpis.upcoming')</div>
                        <div class="value">{{ $stats['upcoming'] }}</div>
                    </div>
                    <span class="icon"><x-svg-icon name="clock-history" :size="20" class="ic-info" /></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form action="{{ route('admin.exams.index') }}" method="GET" class="card filters-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('exams_admin.filters.grade_level')</label>
                <select name="grade_level" class="form-select">
                    <option value="">@lang('exams_admin.filters.all_grades')</option>
                    @for ($g = 1; $g <= 12; $g++)
                        <option value="{{ $g }}" {{ (string) ($filters['grade_level'] ?? '') === (string) $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('exams_admin.filters.teacher')</label>
                <select name="teacher_id" class="form-select">
                    <option value="">@lang('exams_admin.filters.all_teachers')</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t->id }}" {{ (string) ($filters['teacher_id'] ?? '') === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('exams_admin.filters.subject')</label>
                <select name="subject_id" class="form-select">
                    <option value="">@lang('exams_admin.filters.all_subjects')</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ (string) ($filters['subject_id'] ?? '') === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('exams_admin.filters.class')</label>
                <select name="class_id" class="form-select">
                    <option value="">@lang('exams_admin.filters.all_classes')</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" {{ (string) ($filters['class_id'] ?? '') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('exams_admin.filters.type')</label>
                <select name="type" class="form-select">
                    <option value="">@lang('exams_admin.filters.all_types')</option>
                    @foreach (\App\Models\Exam::TYPES as $key => $label)
                        <option value="{{ $key }}" {{ (string) ($filters['type'] ?? '') === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-12 d-flex gap-1 align-items-end">
                <button type="submit" class="btn add-exam-btn flex-grow-1">
                    <x-svg-icon name="search" :size="16" class="me-1" /> @lang('exams_admin.filters.show')
                </button>
                <a href="{{ route('admin.exams.index') }}" class="btn btn-soft" title="@lang('exams_admin.filters.reset')">
                    <x-svg-icon name="arrow-clockwise" :size="16" class="ic-muted" />
                </a>
            </div>
        </div>
    </form>

    {{-- Exams table --}}
    <div class="card">
        @if ($exams->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>@lang('exams_admin.columns.title')</th>
                            <th>@lang('exams_admin.columns.subject')</th>
                            <th>@lang('exams_admin.columns.class')</th>
                            <th>@lang('exams_admin.columns.teacher')</th>
                            <th>@lang('exams_admin.columns.type')</th>
                            <th>@lang('exams_admin.columns.start_time')</th>
                            <th>@lang('exams_admin.columns.questions')</th>
                            <th>@lang('exams_admin.columns.total_marks')</th>
                            <th>@lang('exams_admin.columns.status')</th>
                            <th>@lang('exams_admin.columns.published')</th>
                            <th class="text-end" style="width: 80px;">@lang('exams_admin.columns.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exams as $exam)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.exams.show', $exam) }}" class="text-decoration-none">
                                        <span class="exam-title">{{ $exam->title }}</span>
                                    </a>
                                </td>
                                <td>{{ $exam->subject->name ?? '—' }}</td>
                                <td>{{ $exam->classRoom->name ?? '—' }}</td>
                                <td>{{ $exam->teacher->name ?? '—' }}</td>
                                <td>
                                    <span class="type-chip t-{{ $exam->type }}">{{ $exam->type_label }}</span>
                                </td>
                                <td>
                                    @if ($exam->start_time)
                                        <span class="exam-meta">{{ $exam->start_time->format('Y-m-d') }}</span>
                                        <span class="exam-meta d-block">{{ $exam->start_time->format('H:i') }}</span>
                                    @else
                                        <span class="exam-meta">—</span>
                                    @endif
                                </td>
                                <td>{{ $exam->questions_count }}</td>
                                <td>{{ number_format((float) $exam->total_marks, 1) }}</td>
                                <td>
                                    <span class="status-pill {{ $exam->status }}">{{ $exam->status_label }}</span>
                                </td>
                                <td>
                                    @if ($exam->is_published)
                                        <span class="pub-pill on"><x-svg-icon name="check2" :size="16" class="ic-success me-1" /> @lang('exams_admin.badges.published')</span>
                                    @else
                                        <span class="pub-pill off">@lang('exams_admin.badges.draft')</span>
                                    @endif
                                </td>
                                <td class="text-end row-actions">
                                    <div class="dropdown">
                                        <button type="button" class="btn-icon dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <x-svg-icon name="three-dots-vertical" :size="16" class="ic-muted" />
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('admin.exams.show', $exam) }}"><x-svg-icon name="eye-fill" :size="16" class="ic-info me-1" /> @lang('exams_admin.actions.view')</a>
                                            <a class="dropdown-item" href="{{ route('admin.exams.questions.index', $exam) }}"><x-svg-icon name="list-ol" :size="16" class="ic-navy me-1" /> @lang('exams_admin.actions.questions')</a>
                                            <a class="dropdown-item" href="{{ route('admin.exams.edit', $exam) }}"><x-svg-icon name="pencil-square" :size="16" class="ic-gold me-1" /> @lang('exams_admin.actions.edit')</a>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST" onsubmit="return confirm('@lang('exams_admin.actions.delete_confirm')')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><x-svg-icon name="trash3-fill" :size="16" class="me-1" /> @lang('exams_admin.actions.delete')</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($exams->hasPages())
                <div class="card-footer">{{ $exams->links() }}</div>
            @endif
        @else
            <div class="empty-state">
                <span class="icon-wrap"><x-svg-icon name="calendar-x-fill" :size="48" class="ic-warn" /></span>
                <h5 class="mb-1">@lang('exams_admin.empty.title')</h5>
                <p>@lang('exams_admin.empty.subtitle')</p>
                <a href="{{ route('admin.exams.create') }}" class="btn add-exam-btn">
                    <x-svg-icon name="plus-lg" :size="16" class="me-1" /> @lang('exams_admin.empty.cta')
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
