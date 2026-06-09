@extends('layouts.app')

@section('title', __('class_visits.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .cv-kpis .card { padding: 1rem 1.1rem; }
    body.theme-light .cv-kpis .label { color:#64748b; font-weight:600; font-size:.78rem; letter-spacing:.3px; text-transform:uppercase; margin-bottom:.35rem; }
    body.theme-light .cv-kpis .value { font-size:1.65rem; font-weight:800; color:var(--gold-400); line-height:1; }
    body.theme-light .cv-kpis .icon { width:42px; height:42px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.2rem; }
    body.theme-light .cv-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .cv-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .filters-card .form-label { font-size:.78rem; color:#64748b; font-weight:600; margin-bottom:.25rem; }
    body.theme-light .filters-card .form-control, body.theme-light .filters-card select { border-radius:10px; border:1px solid #e5e7eb; font-size:.88rem; padding:.45rem .7rem; }
    body.theme-light .cv-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .cv-pill.scheduled { background:#f1f5f9; color:#475569; }
    body.theme-light .cv-pill.secret { background:#ede9fe; color:#5b21b6; }
    body.theme-light .cv-pill.teacher_notified { background:#e0f2fe; color:#0369a1; }
    body.theme-light .cv-pill.in_progress { background:#fef9c3; color:#a16207; }
    body.theme-light .cv-pill.completed { background:#ecfdf5; color:#047857; }
    body.theme-light .cv-pill.postponed { background:#fff7ed; color:#c2410c; }
    body.theme-light .cv-pill.cancelled { background:#fef2f2; color:#b91c1c; }
    body.theme-light .cv-pill.missed { background:#f3f4f6; color:#6b7280; }
    body.theme-light .cv-type { font-size:.72rem; font-weight:600; }
    body.theme-light .cv-type.secret { color:#5b21b6; }
    body.theme-light .cv-empty { padding:3.5rem 1rem; text-align:center; }
    body.theme-light .cv-empty .icon-wrap { width:72px; height:72px; border-radius:18px; margin:0 auto 1rem; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.8rem; display:inline-flex; align-items:center; justify-content:center; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('class_visits.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('class_visits.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.class-visits.create') }}" class="btn cv-add-btn">
            <i class="la la-plus"></i> @lang('class_visits.add')
        </a>
    </div>
</div>

<div class="content-body">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- KPI tiles --}}
    <div class="row cv-kpis mb-3">
        @foreach (['total' => 'la-calendar-check', 'scheduled' => 'la-calendar', 'in_progress' => 'la-spinner', 'completed' => 'la-check-circle'] as $key => $icon)
            <div class="col-md-3 col-6 mb-2">
                <div class="card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="label">@lang('class_visits.kpis.'.$key)</div>
                            <div class="value">{{ $stats[$key] }}</div>
                        </div>
                        <span class="icon"><i class="la {{ $icon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form action="{{ route('admin.class-visits.index') }}" method="GET" class="card filters-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.search')</label>
                <input type="text" name="q" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="@lang('class_visits.filters.teacher')">
            </div>
            @if ($schools->count())
                <div class="col-md-3 col-6">
                    <label class="form-label">@lang('class_visits.filters.school')</label>
                    <select name="school_id" class="form-control">
                        <option value="">@lang('class_visits.filters.all')</option>
                        @foreach ($schools as $s)
                            <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.teacher')</label>
                <select name="teacher_id" class="form-control cv-select2">
                    <option value="">@lang('class_visits.filters.all')</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t->id }}" {{ ($filters['teacher_id'] ?? null) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.subject')</label>
                <select name="subject_id" class="form-control cv-select2">
                    <option value="">@lang('class_visits.filters.all')</option>
                    @foreach ($subjects as $sub)
                        <option value="{{ $sub->id }}" {{ ($filters['subject_id'] ?? null) == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.supervisor')</label>
                <select name="supervisor_id" class="form-control cv-select2">
                    <option value="">@lang('class_visits.filters.all')</option>
                    @foreach ($supervisors as $sup)
                        <option value="{{ $sup->id }}" {{ ($filters['supervisor_id'] ?? null) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.section')</label>
                <select name="section_id" class="form-control">
                    <option value="">@lang('class_visits.filters.all')</option>
                    @foreach ($sections as $sec)
                        <option value="{{ $sec->id }}" {{ ($filters['section_id'] ?? null) == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.grade')</label>
                <select name="class_room_id" class="form-control">
                    <option value="">@lang('class_visits.filters.all')</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" {{ ($filters['class_room_id'] ?? null) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.status')</label>
                <select name="status" class="form-control">
                    <option value="">@lang('class_visits.filters.all')</option>
                    @foreach ($statuses as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.date_from')</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('class_visits.filters.date_to')</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3 col-12 d-flex gap-1 align-items-end">
                <button type="submit" class="btn cv-add-btn flex-grow-1"><i class="la la-search"></i> @lang('class_visits.filters.show')</button>
                <a href="{{ route('admin.class-visits.index') }}" class="btn btn-outline-secondary" title="@lang('class_visits.filters.reset')"><i class="la la-redo"></i></a>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="card">
        @if ($visits->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            @if ($schools->count())<th>@lang('class_visits.columns.school')</th>@endif
                            <th>@lang('class_visits.columns.teacher')</th>
                            <th>@lang('class_visits.columns.subject')</th>
                            <th>@lang('class_visits.columns.class')</th>
                            <th>@lang('class_visits.columns.period')</th>
                            <th>@lang('class_visits.columns.date')</th>
                            <th>@lang('class_visits.columns.time')</th>
                            <th>@lang('class_visits.columns.form')</th>
                            <th>@lang('class_visits.columns.supervisor')</th>
                            <th>@lang('class_visits.columns.visit_type')</th>
                            <th>@lang('class_visits.columns.notified')</th>
                            <th>@lang('class_visits.columns.status')</th>
                            <th class="text-end" style="width:90px;">@lang('class_visits.columns.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visits as $visit)
                            @php $eff = $effective($visit); @endphp
                            <tr>
                                @if ($schools->count())<td>{{ $visit->school?->name ?? __('class_visits.dash') }}</td>@endif
                                <td class="fw-bold">{{ $visit->teacher?->name ?? __('class_visits.dash') }}</td>
                                <td>{{ $visit->subject?->name ?? __('class_visits.dash') }}</td>
                                <td>{{ $visit->classRoom?->name ?? ($visit->section?->name ?? __('class_visits.dash')) }}</td>
                                <td>{{ $visit->period_id ? '#'.$visit->period_id : __('class_visits.dash') }}</td>
                                <td><span class="text-muted small">{{ $visit->visit_date?->format('Y-m-d') ?? __('class_visits.dash') }}</span></td>
                                <td><span class="text-muted small">{{ $visit->visit_time ?? __('class_visits.dash') }}</span></td>
                                <td>{{ $visit->form?->title ?? __('class_visits.dash') }}</td>
                                <td>{{ $visit->supervisor?->name ?? __('class_visits.dash') }}</td>
                                <td>
                                    <span class="cv-type {{ $visit->visit_type }}">
                                        {{ __('class_visits.visit_type.'.$visit->visit_type) }}
                                    </span>
                                </td>
                                <td>{{ $visit->notify_teacher ? __('class_visits.yes') : __('class_visits.no') }}</td>
                                <td><span class="cv-pill {{ $eff->value }}">{{ $eff->label() }}</span></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false"><i class="la la-ellipsis-h"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            @if ($eff->value !== 'completed' && $eff->value !== 'cancelled')
                                                <a class="dropdown-item text-success" href="{{ route('admin.class-visits.execute', $visit->id) }}"><i class="la la-play"></i> @lang('class_visits.actions.execute')</a>
                                            @endif
                                            @if ($visit->evaluation_id)
                                                <a class="dropdown-item" href="{{ route('admin.evaluations.execute.show', $visit->evaluation_id) }}"><i class="la la-eye"></i> @lang('class_visits.actions.view_eval')</a>
                                            @endif
                                            @if ($eff->value !== 'completed')
                                                <a class="dropdown-item" href="{{ route('admin.class-visits.edit', $visit->id) }}"><i class="la la-pen"></i> @lang('class_visits.actions.edit')</a>
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.class-visits.destroy', $visit->id) }}" method="POST" onsubmit="return confirm('@lang('class_visits.confirm.delete')')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="la la-trash"></i> @lang('class_visits.actions.delete')</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($visits->hasPages())
                <div class="card-footer">{{ $visits->links() }}</div>
            @endif
        @else
            <div class="cv-empty">
                <span class="icon-wrap"><i class="la la-calendar-times"></i></span>
                <h5 class="mb-1">@lang('class_visits.empty.title')</h5>
                <p class="text-muted">@lang('class_visits.empty.subtitle')</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.cv-select2').select2({ width: '100%' });
        }
    });
</script>
@endpush
