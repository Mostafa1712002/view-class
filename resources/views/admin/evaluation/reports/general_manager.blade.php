@extends('layouts.app')

@section('title', __('eval_reports.general_manager_title'))
@section('body_class','theme-light')

@push('styles')
    @include('admin.evaluation.reports._styles')
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('eval_reports.general_manager_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">@lang('eval_reports.breadcrumb_reports')</li>
                <li class="breadcrumb-item active">@lang('eval_reports.general_manager_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-5 col-12 text-end no-print">
        <a href="{{ route('admin.eval-reports.general-manager', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-outline-secondary">
            <i class="la la-file-csv"></i> @lang('eval_reports.export_csv')
        </a>
        <button type="button" onclick="window.print()" class="btn ev-add-btn"><i class="la la-print"></i> @lang('eval_reports.print')</button>
    </div>
</div>

<div class="content-body">
    {{-- KPI tiles --}}
    <div class="row ev-kpis mb-2">
        @php
            $tiles = [
                ['kpis.teachers', $kpis['teachers'], 'la-chalkboard-teacher'],
                ['kpis.completed', $kpis['completed'], 'la-check-circle'],
                ['kpis.incomplete', $kpis['incomplete'], 'la-hourglass-half'],
                ['kpis.approved', $kpis['approved'], 'la-stamp'],
                ['kpis.pending_approval', $kpis['pending_approval'], 'la-clock'],
                ['kpis.avg_performance', $kpis['avg_pct'] !== null ? $kpis['avg_pct'].'%' : '—', 'la-percent'],
                ['kpis.highest', $kpis['highest'] !== null ? $kpis['highest'].'%' : '—', 'la-arrow-up'],
                ['kpis.lowest', $kpis['lowest'] !== null ? $kpis['lowest'].'%' : '—', 'la-arrow-down'],
                ['kpis.without_evidence', $kpis['without_evidence'], 'la-folder-open'],
                ['kpis.needs_review', $kpis['needs_review'], 'la-exclamation-triangle'],
            ];
        @endphp
        @foreach ($tiles as [$labelKey, $value, $icon])
            <div class="col-md-2 col-6 mb-2">
                <div class="card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="label">@lang('eval_reports.'.$labelKey)</div>
                            <div class="value">{{ $value }}</div>
                        </div>
                        <span class="icon"><i class="la {{ $icon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @include('admin.evaluation.reports._filters', ['mode' => 'general_manager'])

    <div class="card">
        @if ($rows->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 report-table">
                    <thead>
                        <tr>
                            <th>@lang('eval_reports.cols.teacher')</th>
                            <th>@lang('eval_reports.cols.school')</th>
                            <th>@lang('eval_reports.cols.specialization')</th>
                            <th>@lang('eval_reports.cols.evaluations')</th>
                            <th>@lang('eval_reports.cols.evaluator')</th>
                            <th>@lang('eval_reports.cols.final_score')</th>
                            <th>@lang('eval_reports.cols.percentage')</th>
                            <th>@lang('eval_reports.cols.status')</th>
                            <th>@lang('eval_reports.cols.evidence')</th>
                            <th>@lang('eval_reports.cols.eval_date')</th>
                            <th>@lang('eval_reports.cols.last_update')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="fw-bold">{{ $r['teacher'] ?? '—' }}</td>
                                <td>{{ $r['school'] ?? '—' }}</td>
                                <td>{{ $r['specialization'] ?? '—' }}</td>
                                <td>{{ $r['evaluations'] }}</td>
                                <td>
                                    @if ($r['evaluator'])
                                        {{ $r['evaluator'] }}
                                    @else
                                        <span class="text-muted">{{ __('eval_reports.multiple_evaluators') }} ({{ $r['evaluator_count'] }})</span>
                                    @endif
                                </td>
                                <td>{{ $r['final_score'] !== null ? $r['final_score'] : '—' }}</td>
                                <td class="fw-bold">{{ $r['avg_pct'] !== null ? $r['avg_pct'].'%' : '—' }}</td>
                                <td><span class="ev-pill {{ $r['status']?->value }}">{{ $r['status']?->label() }}</span></td>
                                <td>{{ $r['evidence_count'] }}</td>
                                <td>{{ $r['eval_date'] ? \Illuminate\Support\Carbon::parse($r['eval_date'])->format('Y-m-d') : '—' }}</td>
                                <td><span class="text-muted small">{{ $r['last_update'] ? \Illuminate\Support\Carbon::parse($r['last_update'])->format('Y-m-d') : '—' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            @include('admin.evaluation.reports._empty')
        @endif
    </div>
</div>
@endsection
