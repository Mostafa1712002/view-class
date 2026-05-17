@extends('layouts.app')

@section('title', __('weekly_plan.page_title'))
@section('body_class','theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $days = __('weekly_plan.days');
    // Group plans by class -> rows; we display day badge per plan (best-effort
    // from week_start_date + offset is meaningless for a single week_start row,
    // so we show the class+subject as the row and the week's day labels in the
    // weekly header strip).
    $plansByClass = $weekPlans->groupBy(fn($p) => $p->classRoom?->name ?? '—');
@endphp

@push('styles')
<style>
    /* ===== Weekly Plan — light + gold accent (card 66) ============= */
    .wp-header { margin-bottom: 1.25rem; }
    .wp-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .wp-header .subtitle { color: #64748b; font-size: .88rem; }
    .wp-header .breadcrumb {
        padding: 0; margin: 0; background: transparent; font-size: .85rem;
    }
    .wp-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    /* KPI strip */
    .wp-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr));
        gap: .75rem; margin-bottom: 1.25rem; }
    @media (max-width: 768px) {
        .wp-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); }
    }
    .wp-kpi {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .wp-kpi:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(15,23,42,.06), 0 12px 28px rgba(15,23,42,.05); }
    .wp-kpi .ico {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .wp-kpi .ico-gold   { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
    .wp-kpi .ico-green  { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .wp-kpi .ico-amber  { background: linear-gradient(135deg, #fef3c7, #fcd34d); color: #b45309; }
    .wp-kpi .ico-red    { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #b91c1c; }
    .wp-kpi .num   { font-size: 1.45rem; font-weight: 800; color: #0f172a; line-height: 1.1; letter-spacing: -.5px; }
    .wp-kpi .lbl   { font-size: .8rem; color: #64748b; }

    /* Filter card */
    .wp-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1rem 1.1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .wp-card .se-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a; margin-bottom: .8rem;
    }
    .wp-card .se-title i { color: #b45309; font-size: 1.1rem; }
    .wp-form .form-label {
        font-weight: 600; color: #475569; font-size: .82rem; margin-bottom: .25rem;
    }
    .wp-form .form-control, .wp-form .form-select {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .5rem .8rem; font-size: .9rem; color: #0f172a;
        transition: border-color .15s ease, box-shadow .15s ease;
        height: auto;
    }
    .wp-form .form-control:focus, .wp-form .form-select:focus {
        border-color: #fbbf24;
        box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }

    /* Buttons */
    .btn-gold {
        background: linear-gradient(135deg, #fcd34d, #d97706);
        border: 1px solid #d97706; color: #fff;
        font-weight: 600; padding: .5rem 1.1rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        display: inline-flex; align-items: center; gap: .4rem;
        transition: transform .15s ease, box-shadow .2s ease;
    }
    .btn-gold:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(207,160,70,.22); }
    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #334155;
        font-weight: 600; padding: .5rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem;
        transition: all .15s ease;
    }
    .btn-ghost:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .btn-ghost.btn-danger-outline { color: #b91c1c; border-color: #fecaca; }
    .btn-ghost.btn-danger-outline:hover { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .btn-ghost.btn-success-outline { color: #15803d; border-color: #bbf7d0; }
    .btn-ghost.btn-success-outline:hover { background: #dcfce7; color: #14532d; }
    .btn-ghost i { color: #b45309; }

    /* Week navigator */
    .wp-week-nav {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: .8rem; padding: .6rem 1rem; background: #fff;
        border: 1px solid #e5e7eb; border-radius: 14px; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }
    .wp-week-nav .nav-left { display: flex; gap: .4rem; flex-wrap: wrap; }
    .wp-week-nav .nav-range {
        font-size: .9rem; color: #0f172a; font-weight: 600;
    }
    .wp-week-nav .nav-range .from { color: #64748b; font-weight: 400; }
    .wp-week-nav .nav-tools { display: flex; gap: .4rem; flex-wrap: wrap; }

    /* Days strip */
    .wp-days-strip {
        display: grid; grid-template-columns: repeat(5, minmax(0,1fr));
        gap: .5rem; margin-bottom: 1rem;
    }
    @media (max-width: 768px) {
        .wp-days-strip { grid-template-columns: repeat(2, minmax(0,1fr)); }
    }
    .wp-day-chip {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: .55rem .8rem; text-align: center;
        box-shadow: 0 1px 2px rgba(15,23,42,.03);
    }
    .wp-day-chip .day-name { font-weight: 700; color: #0f172a; font-size: .92rem; }
    .wp-day-chip .day-date { font-size: .75rem; color: #64748b; }

    /* Table */
    .wp-surface {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .wp-table { margin: 0; width: 100%; }
    .wp-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .8rem 1rem;
        white-space: nowrap;
    }
    .wp-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #0f172a; font-size: .9rem; }
    .wp-table tbody tr { transition: background .15s ease; }
    .wp-table tbody tr:hover { background: #fafbfc; }
    .wp-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }
    .wp-table .col-narrow { max-width: 180px; }
    .wp-table .col-narrow small { display:block; white-space:normal; line-height:1.4; }

    /* Group header row */
    .wp-group-row td {
        background: #fefce8 !important; color: #b45309; font-weight: 700;
        font-size: .85rem; padding: .55rem 1rem !important;
        border-top: 1px solid #fde68a; border-bottom: 1px solid #fde68a;
    }

    /* Status badges */
    .wp-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px;
        font-size: .72rem; font-weight: 600;
    }
    .wp-badge-prepared { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .wp-badge-not-prepared { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .wp-badge-locked { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    /* Action icon buttons */
    .wp-actions { display: inline-flex; gap: .3rem; }
    .wp-actions .btn-icon {
        width: 32px; height: 32px; display: inline-flex; align-items: center;
        justify-content: center; border-radius: 8px; border: 1px solid #e2e8f0;
        background: #fff; color: #475569; transition: all .15s ease;
    }
    .wp-actions .btn-icon:hover { background: #f8fafc; color: #b45309; border-color: #fde68a; }
    .wp-actions .btn-icon.success:hover { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
    .wp-actions .btn-icon.warning:hover { background: #fef3c7; color: #b45309; border-color: #fde68a; }

    /* Empty state */
    .wp-empty {
        background: #fff; border: 1px dashed #e5e7eb; border-radius: 14px;
        padding: 3rem 1rem; text-align: center; color: #64748b;
    }
    .wp-empty i { font-size: 3rem; color: #cbd5e1; }
    .wp-empty h4 { color: #0f172a; font-weight: 700; margin: .8rem 0 .3rem; }

    /* Truncate text-cell content nicely */
    .wp-clip {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; max-width: 180px; line-height: 1.4;
    }
</style>
@endpush

@section('content')
<div class="content-body wp-page">

    {{-- Header --}}
    <div class="wp-header">
        <h2>@lang('weekly_plan.page_title')</h2>
        <div class="subtitle">@lang('weekly_plan.subtitle')</div>
        <ol class="breadcrumb mt-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('weekly_plan.breadcrumb')</li>
        </ol>
    </div>

    @include('components.alerts')

    {{-- KPI strip --}}
    <div class="wp-kpis">
        <div class="wp-kpi">
            <div class="ico ico-gold"><i class="la la-calendar-week"></i></div>
            <div>
                <div class="num">{{ number_format($kpis['total'] ?? 0) }}</div>
                <div class="lbl">@lang('weekly_plan.kpi_total')</div>
            </div>
        </div>
        <div class="wp-kpi">
            <div class="ico ico-green"><i class="la la-check-circle"></i></div>
            <div>
                <div class="num">{{ number_format($kpis['prepared'] ?? 0) }}</div>
                <div class="lbl">@lang('weekly_plan.kpi_prepared')</div>
            </div>
        </div>
        <div class="wp-kpi">
            <div class="ico ico-amber"><i class="la la-clock"></i></div>
            <div>
                <div class="num">{{ number_format($kpis['not_prepared'] ?? 0) }}</div>
                <div class="lbl">@lang('weekly_plan.kpi_not_prepared')</div>
            </div>
        </div>
        <div class="wp-kpi">
            <div class="ico ico-red"><i class="la la-lock"></i></div>
            <div>
                <div class="num">{{ number_format($kpis['locked'] ?? 0) }}</div>
                <div class="lbl">@lang('weekly_plan.kpi_locked')</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="wp-card">
        <div class="se-title">
            <i class="la la-search"></i>
            @lang('weekly_plan.filters_title')
        </div>
        <form method="GET" action="{{ route('manage.weekly-plans.index') }}" class="wp-form">
            <input type="hidden" name="view" value="grid">
            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
            <div class="row g-2">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_grade')</label>
                    <select name="grade_level" class="form-control form-select">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        @foreach($gradeLevels as $g)
                            <option value="{{ $g }}" @selected(request('grade_level') == $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_class')</label>
                    <select name="class_id" class="form-control form-select">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_teacher')</label>
                    <select name="teacher_id" class="form-control form-select">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" @selected(request('teacher_id') == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_subject')</label>
                    <select name="subject_id" class="form-control form-select">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(request('subject_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_status')</label>
                    <select name="status" class="form-control form-select">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        <option value="prepared" @selected(request('status') === 'prepared')>@lang('weekly_plan.filter_status_prepared')</option>
                        <option value="not_prepared" @selected(request('status') === 'not_prepared')>@lang('weekly_plan.filter_status_not_prepared')</option>
                    </select>
                </div>
                <div class="col-md-9 d-flex align-items-end gap-2">
                    <button type="submit" class="btn-gold">
                        <i class="la la-search"></i> @lang('weekly_plan.btn_search')
                    </button>
                    <a href="{{ route('manage.weekly-plans.index', ['view' => 'grid', 'week_start' => $weekStart->toDateString()]) }}" class="btn-ghost">
                        <i class="la la-times"></i> @lang('weekly_plan.btn_reset')
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Week navigator --}}
    <div class="wp-week-nav">
        <div class="nav-left">
            <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['view' => 'grid', 'week_start' => $weekStart->copy()->subWeek()->toDateString()])) }}" class="btn-ghost">
                <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> @lang('weekly_plan.week_prev')
            </a>
            <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['view' => 'grid', 'week_start' => now()->startOfWeek(\Carbon\Carbon::SUNDAY)->toDateString()])) }}" class="btn-gold">
                <i class="la la-calendar-day"></i> @lang('weekly_plan.week_now')
            </a>
            <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['view' => 'grid', 'week_start' => $weekStart->copy()->addWeek()->toDateString()])) }}" class="btn-ghost">
                @lang('weekly_plan.week_next') <i class="la la-arrow-{{ $isRtl ? 'left' : 'right' }}"></i>
            </a>
        </div>
        <div class="nav-range">
            <span class="from">@lang('weekly_plan.week_range_label'):</span>
            {{ $weekStart->format('Y-m-d') }}
            <span class="from">@lang('weekly_plan.week_range_to')</span>
            {{ $weekEnd->format('Y-m-d') }}
        </div>
        <div class="nav-tools">
            <a href="{{ route('manage.weekly-plans.pdf', request()->query()) }}" target="_blank" class="btn-ghost btn-danger-outline">
                <i class="la la-file-pdf"></i> @lang('weekly_plan.btn_pdf')
            </a>
            <button type="button" class="btn-ghost btn-success-outline" disabled title="@lang('weekly_plan.btn_excel_soon')">
                <i class="la la-file-excel"></i> @lang('weekly_plan.btn_excel')
            </button>
            <a href="{{ route('manage.weekly-plan-notes.index') }}" class="btn-ghost">
                <i class="la la-sticky-note"></i> @lang('weekly_plan.btn_ready_notes')
            </a>
            <a href="{{ route('manage.weekly-plans.create') }}" class="btn-gold">
                <i class="la la-plus"></i> @lang('weekly_plan.btn_add_plan')
            </a>
        </div>
    </div>

    {{-- Days of the school week strip (Sun..Thu) --}}
    <div class="wp-days-strip">
        @for($i = 0; $i < 5; $i++)
            @php $d = $weekStart->copy()->addDays($i); @endphp
            <div class="wp-day-chip">
                <div class="day-name">{{ $days[$i] ?? '' }}</div>
                <div class="day-date">{{ $d->format('Y-m-d') }}</div>
            </div>
        @endfor
    </div>

    {{-- Plans grouped by class --}}
    @if($weekPlans->isEmpty())
        <div class="wp-empty">
            <i class="la la-calendar-times"></i>
            <h4>@lang('weekly_plan.empty_title')</h4>
            <div>@lang('weekly_plan.empty_hint')</div>
        </div>
    @else
        <div class="wp-surface">
            <div class="table-responsive">
                <table class="wp-table">
                    <thead>
                        <tr>
                            <th>@lang('weekly_plan.th_status')</th>
                            <th>@lang('weekly_plan.th_teacher')</th>
                            <th>@lang('weekly_plan.th_subject')</th>
                            <th>@lang('weekly_plan.th_lesson')</th>
                            <th>@lang('weekly_plan.th_objectives')</th>
                            <th>@lang('weekly_plan.th_homework')</th>
                            <th>@lang('weekly_plan.th_attachments')</th>
                            <th>@lang('weekly_plan.th_notes')</th>
                            <th class="text-center">@lang('weekly_plan.th_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plansByClass as $className => $plans)
                            <tr class="wp-group-row">
                                <td colspan="9">
                                    <i class="la la-users"></i>
                                    @lang('weekly_plan.th_class'): <strong>{{ $className }}</strong>
                                    <span class="text-muted small">({{ $plans->count() }})</span>
                                </td>
                            </tr>
                            @foreach($plans as $plan)
                                @php $attachments = is_array($plan->attachments ?? null) ? $plan->attachments : []; @endphp
                                <tr>
                                    <td>
                                        @if($plan->is_locked)
                                            <span class="wp-badge wp-badge-locked"><i class="la la-lock"></i> @lang('weekly_plan.status_locked')</span>
                                        @elseif($plan->is_prepared)
                                            <span class="wp-badge wp-badge-prepared"><i class="la la-check"></i> @lang('weekly_plan.status_prepared')</span>
                                        @else
                                            <span class="wp-badge wp-badge-not-prepared"><i class="la la-clock"></i> @lang('weekly_plan.status_not_prepared')</span>
                                        @endif
                                    </td>
                                    <td>{{ $plan->teacher?->name ?? '—' }}</td>
                                    <td>{{ $plan->subject?->name ?? '—' }}</td>
                                    <td class="col-narrow"><div class="wp-clip">{{ $plan->topics ?: '—' }}</div></td>
                                    <td class="col-narrow"><div class="wp-clip">{{ $plan->objectives ?: '—' }}</div></td>
                                    <td class="col-narrow"><div class="wp-clip">{{ $plan->homework ?: '—' }}</div></td>
                                    <td class="text-center">
                                        @if(count($attachments) > 0)
                                            <span class="wp-badge wp-badge-prepared"><i class="la la-paperclip"></i> {{ count($attachments) }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="col-narrow"><div class="wp-clip">{{ $plan->notes ?: '—' }}</div></td>
                                    <td class="text-center">
                                        <div class="wp-actions">
                                            <form action="{{ route('manage.weekly-plans.mark-prepared', $plan) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn-icon {{ $plan->is_prepared ? 'warning' : 'success' }}" title="{{ $plan->is_prepared ? __('weekly_plan.status_not_prepared') : __('weekly_plan.status_prepared') }}">
                                                    <i class="la la-{{ $plan->is_prepared ? 'undo' : 'check' }}"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('manage.weekly-plans.show', $plan) }}" class="btn-icon" title="@lang('common.show')"><i class="la la-eye"></i></a>
                                            <a href="{{ route('manage.weekly-plans.edit', $plan) }}" class="btn-icon" title="@lang('common.edit')"><i class="la la-edit"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
