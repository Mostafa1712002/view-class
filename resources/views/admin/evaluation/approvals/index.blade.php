@extends('layouts.app')

@section('title', __('eval_approval.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-kpis .card { padding: 1rem 1.1rem; }
    body.theme-light .ev-kpis .label { color:#64748b; font-weight:600; font-size:.78rem; letter-spacing:.3px; text-transform:uppercase; margin-bottom:.35rem; }
    body.theme-light .ev-kpis .value { font-size:1.65rem; font-weight:800; color:var(--gold-400); line-height:1; }
    body.theme-light .ev-kpis .icon { width:42px; height:42px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.2rem; }
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .filters-card .form-label { font-size:.78rem; color:#64748b; font-weight:600; margin-bottom:.25rem; }
    body.theme-light .filters-card .form-control, body.theme-light .filters-card select { border-radius:10px; border:1px solid #e5e7eb; font-size:.88rem; padding:.45rem .7rem; }
    body.theme-light .ev-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .ev-pill.completed { background:#e0f2fe; color:#0369a1; }
    body.theme-light .ev-pill.pending_approval { background:#fef9c3; color:#a16207; }
    body.theme-light .ev-pill.needs_review { background:#ffedd5; color:#c2410c; }
    body.theme-light .ev-pill.approved { background:#ecfdf5; color:#047857; }
    body.theme-light .ev-pill.rejected { background:#fef2f2; color:#b91c1c; }
    body.theme-light .ev-empty { padding:3.5rem 1rem; text-align:center; }
    body.theme-light .ev-empty .icon-wrap { width:72px; height:72px; border-radius:18px; margin:0 auto 1rem; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.8rem; display:inline-flex; align-items:center; justify-content:center; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('eval_approval.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('eval_approval.breadcrumb')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- KPI tiles --}}
    <div class="row ev-kpis mb-3">
        @php
            // count tiles + percentage tiles (#207 analytical metrics)
            $countTiles = [
                'teachers' => 'la-chalkboard-teacher', 'completed' => 'la-check', 'pending' => 'la-hourglass-half',
                'approved' => 'la-check-double', 'needs_review' => 'la-exclamation-triangle',
                'items_pending_review' => 'la-list', 'evidence_pending' => 'la-paperclip',
            ];
            $pctTiles = ['avg_performance' => 'la-chart-line', 'max_pct' => 'la-arrow-up', 'min_pct' => 'la-arrow-down'];
        @endphp
        @foreach ($countTiles as $key => $icon)
            <div class="col-md-3 col-6 mb-2">
                <div class="card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="label">@lang('eval_approval.kpis.'.$key)</div>
                            <div class="value">{{ $stats[$key] ?? 0 }}</div>
                        </div>
                        <span class="icon"><i class="la {{ $icon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
        @foreach ($pctTiles as $key => $icon)
            <div class="col-md-3 col-6 mb-2">
                <div class="card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="label">@lang('eval_approval.kpis.'.$key)</div>
                            <div class="value">{{ rtrim(rtrim(number_format((float) ($stats[$key] ?? 0), 1), '0'), '.') }}%</div>
                        </div>
                        <span class="icon"><i class="la {{ $icon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form action="{{ route('admin.evaluations.approvals.index') }}" method="GET" class="card filters-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-6">
                <label class="form-label">@lang('eval_approval.filters.status')</label>
                <select name="status" class="form-control">
                    <option value="">@lang('eval_approval.filters.all')</option>
                    @foreach ($statuses as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-6">
                <label class="form-label">@lang('eval_approval.filters.form')</label>
                <select name="form" class="form-control select2">
                    <option value="">@lang('eval_approval.filters.all')</option>
                    @foreach ($forms as $f)
                        <option value="{{ $f->id }}" {{ (int) ($filters['form'] ?? 0) === $f->id ? 'selected' : '' }}>{{ $f->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-12 d-flex gap-1 align-items-end">
                <button type="submit" class="btn ev-add-btn flex-grow-1"><i class="la la-search"></i> @lang('eval_approval.filters.show')</button>
                <a href="{{ route('admin.evaluations.approvals.index') }}" class="btn btn-outline-secondary" title="@lang('eval_approval.filters.reset')"><i class="la la-redo"></i></a>
            </div>
        </div>
    </form>

    {{-- Queue table --}}
    <div class="card">
        @if ($evaluations->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>@lang('eval_approval.columns.id')</th>
                            <th>@lang('eval_approval.columns.form')</th>
                            <th>@lang('eval_approval.columns.subject')</th>
                            <th>@lang('eval_approval.columns.evaluator')</th>
                            <th>@lang('eval_approval.columns.score')</th>
                            <th>@lang('eval_approval.columns.status')</th>
                            <th>@lang('eval_approval.columns.submitted')</th>
                            <th class="text-end" style="width:90px;">@lang('eval_approval.columns.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($evaluations as $ev)
                            <tr>
                                <td class="fw-bold">#{{ $ev->id }}</td>
                                <td>{{ $ev->form?->title }}</td>
                                <td>{{ $ev->subject?->name ?? '—' }}</td>
                                <td>{{ $ev->evaluator?->name ?? '—' }}</td>
                                <td>{{ $ev->percentage !== null ? $ev->percentage.'%' : '—' }}</td>
                                <td><span class="ev-pill {{ $ev->status?->value }}">{{ $ev->status?->label() }}</span></td>
                                <td><span class="text-muted small">{{ optional($ev->submitted_at)->format('Y-m-d') ?? '—' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.evaluations.approvals.show', $ev->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="la la-eye"></i> @lang('eval_approval.actions.view')
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($evaluations->hasPages())
                <div class="card-footer">{{ $evaluations->links() }}</div>
            @endif
        @else
            <div class="ev-empty">
                <span class="icon-wrap"><i class="la la-clipboard-check"></i></span>
                <h5 class="mb-1">@lang('eval_approval.empty_title')</h5>
                <p class="text-muted">@lang('eval_approval.empty_subtitle')</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function ($) { if ($.fn.select2) { $('.select2').select2({ width: '100%' }); } });
</script>
@endpush
