@extends('layouts.app')

@section('title', __('eval_reports.supervisors_title'))
@section('body_class','theme-light')

@push('styles')
    @include('admin.evaluation.reports._styles')
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('eval_reports.supervisors_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">@lang('eval_reports.breadcrumb_reports')</li>
                <li class="breadcrumb-item active">@lang('eval_reports.supervisors_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-5 col-12 text-end no-print">
        <a href="{{ route('admin.eval-reports.supervisors-detailed', request()->query()) }}" class="btn btn-outline-secondary">
            <i class="la la-list"></i> @lang('eval_reports.supervisors_detailed_title')
        </a>
        <a href="{{ route('admin.eval-reports.supervisors', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-outline-secondary">
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
                ['kpis.supervisors', $kpis['supervisors'], 'la-user-tie'],
                ['kpis.total_visits', $kpis['total_visits'], 'la-walking'],
                ['kpis.total_evals', $kpis['total_evals'], 'la-clipboard-check'],
                ['kpis.completed', $kpis['completed'], 'la-check-circle'],
                ['kpis.incomplete', $kpis['incomplete'], 'la-hourglass-half'],
                ['kpis.postponed_visits', $kpis['postponed_visits'], 'la-pause-circle'],
                ['kpis.cancelled_visits', $kpis['cancelled_visits'], 'la-ban'],
                ['kpis.avg_pct', $kpis['avg_pct'] !== null ? $kpis['avg_pct'].'%' : '—', 'la-percent'],
                ['kpis.completion_pct', $kpis['completion_pct'] !== null ? $kpis['completion_pct'].'%' : '—', 'la-tasks'],
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
        <div class="col-md-2 col-6 mb-2">
            <div class="card h-100">
                <div class="label">@lang('eval_reports.kpis.top_supervisor')</div>
                <div class="value small">{{ $kpis['top_supervisor'] ?? '—' }}@if($kpis['top_pct'] !== null) <span class="text-muted small">({{ $kpis['top_pct'] }}%)</span>@endif</div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <div class="card h-100">
                <div class="label">@lang('eval_reports.kpis.low_supervisor')</div>
                <div class="value small">{{ $kpis['low_supervisor'] ?? '—' }}@if($kpis['low_pct'] !== null) <span class="text-muted small">({{ $kpis['low_pct'] }}%)</span>@endif</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    @include('admin.evaluation.reports._filters', ['mode' => 'supervisors'])

    {{-- Table --}}
    <div class="card">
        @if ($rows->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 report-table">
                    <thead>
                        <tr>
                            <th>@lang('eval_reports.cols.supervisor')</th>
                            <th>@lang('eval_reports.cols.scheduled')</th>
                            <th>@lang('eval_reports.cols.executed')</th>
                            <th>@lang('eval_reports.cols.not_executed')</th>
                            <th>@lang('eval_reports.cols.evaluations')</th>
                            <th>@lang('eval_reports.cols.completed')</th>
                            <th>@lang('eval_reports.cols.incomplete')</th>
                            <th>@lang('eval_reports.cols.avg_pct')</th>
                            <th>@lang('eval_reports.cols.completion_pct')</th>
                            <th>@lang('eval_reports.cols.last_visit')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="fw-bold">{{ $r['supervisor_name'] }}</td>
                                <td>{{ $r['scheduled'] }}</td>
                                <td>{{ $r['executed'] }}</td>
                                <td>{{ $r['not_executed'] }}</td>
                                <td>{{ $r['evaluations'] }}</td>
                                <td>{{ $r['completed'] }}</td>
                                <td>{{ $r['incomplete'] }}</td>
                                <td>{{ $r['avg_pct'] !== null ? $r['avg_pct'].'%' : '—' }}</td>
                                <td>{{ $r['completion_pct'] !== null ? $r['completion_pct'].'%' : '—' }}</td>
                                <td>{{ $r['last_visit'] ? \Illuminate\Support\Carbon::parse($r['last_visit'])->format('Y-m-d') : '—' }}</td>
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
