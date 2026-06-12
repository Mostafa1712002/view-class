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

    /* Auto-search toggle in filter title */
    .wp-auto-toggle {
        margin-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: auto;
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .8rem; font-weight: 500; color: #64748b; cursor: pointer; margin-bottom: 0;
    }
    .wp-auto-toggle input { accent-color: #cfa046; }
    .ml-auto { margin-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: auto; }

    /* Column customization dropdown
       Using position:fixed so it escapes any overflow:hidden ancestor (e.g. .app-content).
       JS repositions it on open via getBoundingClientRect(). */
    .wp-cols-dropdown { position: relative; display: inline-block; }
    .wp-cols-menu {
        display: none; position: fixed; z-index: 9999;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        box-shadow: 0 8px 24px rgba(15,23,42,.12); padding: .5rem; min-width: 200px;
    }
    .wp-cols-menu.open { display: block; }
    .wp-cols-menu label {
        display: flex; align-items: center; gap: .5rem; padding: .35rem .5rem;
        border-radius: 8px; font-size: .85rem; color: #334155; cursor: pointer; margin: 0;
    }
    .wp-cols-menu label:hover { background: #f8fafc; }
    .wp-cols-menu input { accent-color: #cfa046; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('weekly_plan.page_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('weekly_plan.breadcrumb')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('manage.weekly-plans.create') }}" class="btn btn-primary btn-sm">
            <i class="la la-plus"></i> @lang('weekly_plan.btn_add_plan')
        </a>
        <a href="{{ route('manage.weekly-plan-notes.index') }}" class="btn btn-secondary btn-sm">
            <i class="la la-sticky-note"></i> @lang('weekly_plan.btn_ready_notes')
        </a>
    </div>
</div>

<div class="content-body wp-page">

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
    @php
        $hasAdvanced = request()->filled('teacher_id') || request()->filled('subject_id')
            || request()->filled('status') || request()->filled('q') || request()->filled('date');
    @endphp
    <div class="wp-card">
        <div class="se-title">
            <i class="la la-search"></i>
            @lang('weekly_plan.filters_title')
            <label class="wp-auto-toggle ml-auto">
                <input type="checkbox" id="wpAutoSearch" checked>
                <span>@lang('weekly_plan.auto_search_label')</span>
            </label>
        </div>
        <form method="GET" action="{{ route('manage.weekly-plans.index') }}" class="wp-form" id="wpFilterForm">
            <input type="hidden" name="view" value="grid">
            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
            {{-- Primary (always visible) --}}
            <div class="row g-2">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_grade')</label>
                    <select name="grade_level" class="form-control form-select wp-auto">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        @foreach($gradeLevels as $g)
                            <option value="{{ $g }}" @selected(request('grade_level') == $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_class')</label>
                    <select name="class_id" class="form-control form-select wp-auto">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_search')</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control wp-auto-text"
                           placeholder="@lang('weekly_plan.filter_search_placeholder')">
                </div>
                <div class="col-md-3 col-sm-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="la la-search"></i> @lang('weekly_plan.btn_search')
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="wpAdvancedToggle">
                        <i class="la la-sliders-h"></i> @lang('weekly_plan.btn_advanced')
                    </button>
                </div>
            </div>
            {{-- Advanced (collapsible) --}}
            <div class="row g-2 mt-1 wp-advanced" id="wpAdvanced" style="{{ $hasAdvanced ? '' : 'display:none;' }}">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_teacher')</label>
                    <select name="teacher_id" class="form-control form-select wp-auto">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" @selected(request('teacher_id') == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_subject')</label>
                    <select name="subject_id" class="form-control form-select wp-auto">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(request('subject_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_status')</label>
                    <select name="status" class="form-control form-select wp-auto">
                        <option value="">@lang('weekly_plan.filter_all')</option>
                        <option value="prepared" @selected(request('status') === 'prepared')>@lang('weekly_plan.filter_status_prepared')</option>
                        <option value="not_prepared" @selected(request('status') === 'not_prepared')>@lang('weekly_plan.filter_status_not_prepared')</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">@lang('weekly_plan.filter_date')</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-control wp-auto">
                </div>
                <div class="col-12 d-flex align-items-end gap-2">
                    <a href="{{ route('manage.weekly-plans.index', ['view' => 'grid', 'week_start' => $weekStart->toDateString()]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="la la-times"></i> @lang('weekly_plan.btn_reset')
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Week navigator --}}
    <div class="wp-week-nav">
        <div class="nav-left">
            <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['view' => 'grid', 'week_start' => $weekStart->copy()->subWeek()->toDateString()])) }}" class="btn btn-outline-secondary btn-sm">
                <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> @lang('weekly_plan.week_prev')
            </a>
            <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['view' => 'grid', 'week_start' => now()->startOfWeek(\Carbon\Carbon::SUNDAY)->toDateString()])) }}" class="btn btn-primary btn-sm">
                <i class="la la-calendar-day"></i> @lang('weekly_plan.week_now')
            </a>
            <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['view' => 'grid', 'week_start' => $weekStart->copy()->addWeek()->toDateString()])) }}" class="btn btn-outline-secondary btn-sm">
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
            <a href="{{ route('manage.weekly-plans.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                <i class="la la-file-pdf"></i> @lang('weekly_plan.btn_pdf')
            </a>
            <a href="{{ route('manage.weekly-plans.excel', request()->query()) }}" class="btn btn-outline-success btn-sm">
                <i class="la la-file-excel"></i> @lang('weekly_plan.btn_excel')
            </a>
            <div class="wp-cols-dropdown">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="wpColsToggle">
                    <i class="la la-columns"></i> @lang('weekly_plan.btn_columns')
                </button>
                <div class="wp-cols-menu" id="wpColsMenu"></div>
            </div>
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
                <table class="wp-table" id="wpTable">
                    <thead>
                        <tr>
                            <th data-col="status">@lang('weekly_plan.th_status')</th>
                            <th data-col="teacher">@lang('weekly_plan.th_teacher')</th>
                            <th data-col="subject">@lang('weekly_plan.th_subject')</th>
                            <th data-col="lesson">@lang('weekly_plan.th_lesson')</th>
                            <th data-col="objectives">@lang('weekly_plan.th_objectives')</th>
                            <th data-col="homework">@lang('weekly_plan.th_homework')</th>
                            <th data-col="exams">@lang('weekly_plan.th_exams')</th>
                            <th data-col="attachments">@lang('weekly_plan.th_attachments')</th>
                            <th data-col="notes">@lang('weekly_plan.th_notes')</th>
                            <th class="text-center" data-col="actions" data-col-locked="1">@lang('weekly_plan.th_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plansByClass as $className => $plans)
                            <tr class="wp-group-row">
                                <td colspan="10">
                                    <i class="la la-users"></i>
                                    @lang('weekly_plan.th_class'): <strong>{{ $className }}</strong>
                                    <span class="text-muted small">({{ $plans->count() }})</span>
                                </td>
                            </tr>
                            @foreach($plans as $plan)
                                @php $attachments = is_array($plan->attachments ?? null) ? $plan->attachments : []; @endphp
                                <tr>
                                    <td data-col="status">
                                        @if($plan->is_locked)
                                            <span class="wp-badge wp-badge-locked"><i class="la la-lock"></i> @lang('weekly_plan.status_locked')</span>
                                        @elseif($plan->is_prepared)
                                            <span class="wp-badge wp-badge-prepared"><i class="la la-check"></i> @lang('weekly_plan.status_prepared')</span>
                                        @else
                                            <span class="wp-badge wp-badge-not-prepared"><i class="la la-clock"></i> @lang('weekly_plan.status_not_prepared')</span>
                                        @endif
                                    </td>
                                    <td data-col="teacher">{{ $plan->teacher?->name ?? '—' }}</td>
                                    <td data-col="subject">{{ $plan->subject?->name ?? '—' }}</td>
                                    <td class="col-narrow" data-col="lesson"><div class="wp-clip">{{ $plan->lesson_title ?: ($plan->topics ?: '—') }}</div></td>
                                    <td class="col-narrow" data-col="objectives"><div class="wp-clip">{{ $plan->objectives ?: '—' }}</div></td>
                                    <td class="col-narrow" data-col="homework"><div class="wp-clip">{{ $plan->homework ?: '—' }}</div></td>
                                    <td class="col-narrow" data-col="exams"><div class="wp-clip">{{ $plan->exams ?: '—' }}</div></td>
                                    <td class="text-center" data-col="attachments">
                                        @if(count($attachments) > 0)
                                            <span class="wp-badge wp-badge-prepared"><i class="la la-paperclip"></i> {{ count($attachments) }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="col-narrow" data-col="notes"><div class="wp-clip">{{ $plan->notes ?: '—' }}</div></td>
                                    <td class="text-center" data-col="actions">
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

@push('scripts')
<script>
(function () {
    var form = document.getElementById('wpFilterForm');

    // ---- Advanced search toggle ----
    var advToggle = document.getElementById('wpAdvancedToggle');
    var adv = document.getElementById('wpAdvanced');
    if (advToggle && adv) {
        advToggle.addEventListener('click', function () {
            adv.style.display = (adv.style.display === 'none' || adv.style.display === '') ? 'flex' : 'none';
        });
    }

    // ---- Auto-search (submit on change, debounce text) ----
    var autoChk = document.getElementById('wpAutoSearch');
    var AUTO_KEY = 'wp_auto_search';
    if (autoChk) {
        var saved = localStorage.getItem(AUTO_KEY);
        if (saved !== null) { autoChk.checked = (saved === '1'); }
        autoChk.addEventListener('change', function () {
            localStorage.setItem(AUTO_KEY, autoChk.checked ? '1' : '0');
        });
    }
    function autoOn() { return !autoChk || autoChk.checked; }

    if (form) {
        form.querySelectorAll('.wp-auto').forEach(function (el) {
            el.addEventListener('change', function () { if (autoOn()) form.submit(); });
        });
        var debounce;
        form.querySelectorAll('.wp-auto-text').forEach(function (el) {
            el.addEventListener('input', function () {
                if (!autoOn()) return;
                clearTimeout(debounce);
                debounce = setTimeout(function () { form.submit(); }, 500);
            });
        });
    }

    // ---- Column customization (localStorage-persisted) ----
    // Toggle and menu are wired regardless of whether the table is present
    // (empty-week shows no table, but the button must still open the menu so
    // the user can pre-configure columns before adding plans).
    var table = document.getElementById('wpTable');
    var colsToggle = document.getElementById('wpColsToggle');
    var colsMenu = document.getElementById('wpColsMenu');
    var COLS_KEY = 'wp_hidden_cols';

    if (colsToggle && colsMenu) {
        var labels = {
            status: @json(__('weekly_plan.th_status')),
            teacher: @json(__('weekly_plan.th_teacher')),
            subject: @json(__('weekly_plan.th_subject')),
            lesson: @json(__('weekly_plan.th_lesson')),
            objectives: @json(__('weekly_plan.th_objectives')),
            homework: @json(__('weekly_plan.th_homework')),
            exams: @json(__('weekly_plan.th_exams')),
            attachments: @json(__('weekly_plan.th_attachments')),
            notes: @json(__('weekly_plan.th_notes'))
        };
        var hidden = JSON.parse(localStorage.getItem(COLS_KEY) || '[]');

        function applyCols() {
            if (!table) { return; }   // no table on empty-week — safe no-op
            Object.keys(labels).forEach(function (col) {
                var show = hidden.indexOf(col) === -1;
                table.querySelectorAll('[data-col="' + col + '"]').forEach(function (cell) {
                    cell.style.display = show ? '' : 'none';
                });
            });
        }
        function buildMenu() {
            while (colsMenu.firstChild) { colsMenu.removeChild(colsMenu.firstChild); }
            Object.keys(labels).forEach(function (col) {
                var lab = document.createElement('label');
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.checked = hidden.indexOf(col) === -1;
                cb.addEventListener('change', function () {
                    if (cb.checked) { hidden = hidden.filter(function (c) { return c !== col; }); }
                    else if (hidden.indexOf(col) === -1) { hidden.push(col); }
                    localStorage.setItem(COLS_KEY, JSON.stringify(hidden));
                    applyCols();
                });
                lab.appendChild(cb);
                lab.appendChild(document.createTextNode(labels[col]));
                colsMenu.appendChild(lab);
            });
        }
        buildMenu();
        applyCols();

        function positionMenu() {
            var rect = colsToggle.getBoundingClientRect();
            var isRtl = document.documentElement.dir === 'rtl';
            colsMenu.style.top = (rect.bottom + 4) + 'px';
            if (isRtl) {
                colsMenu.style.right = (window.innerWidth - rect.right) + 'px';
                colsMenu.style.left = 'auto';
            } else {
                colsMenu.style.left = rect.left + 'px';
                colsMenu.style.right = 'auto';
            }
        }
        colsToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasOpen = colsMenu.classList.contains('open');
            colsMenu.classList.remove('open');
            if (!wasOpen) {
                positionMenu();
                colsMenu.classList.add('open');
            }
        });
        document.addEventListener('click', function (e) {
            if (!colsMenu.contains(e.target) && e.target !== colsToggle) {
                colsMenu.classList.remove('open');
            }
        });
        window.addEventListener('scroll', function () { colsMenu.classList.remove('open'); }, true);
        window.addEventListener('resize', function () { colsMenu.classList.remove('open'); });
    }
})();
</script>
@endpush
