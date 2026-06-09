@extends('layouts.app')

@section('title', __('eval_reports.supervisors_detailed_title'))
@section('body_class','theme-light')

@push('styles')
    @include('admin.evaluation.reports._styles')
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('eval_reports.supervisors_detailed_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">@lang('eval_reports.breadcrumb_reports')</li>
                <li class="breadcrumb-item active">@lang('eval_reports.supervisors_detailed_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-5 col-12 text-end no-print">
        <a href="{{ route('admin.eval-reports.supervisors', request()->query()) }}" class="btn btn-outline-secondary">
            <i class="la la-chart-pie"></i> @lang('eval_reports.supervisors_title')
        </a>
        <a href="{{ route('admin.eval-reports.supervisors-detailed', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-outline-secondary">
            <i class="la la-file-csv"></i> @lang('eval_reports.export_csv')
        </a>
        <button type="button" onclick="window.print()" class="btn ev-add-btn"><i class="la la-print"></i> @lang('eval_reports.print')</button>
    </div>
</div>

<div class="content-body">
    @include('admin.evaluation.reports._filters', ['mode' => 'supervisors_detailed'])

    <div class="card">
        @if ($rows->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 report-table">
                    <thead>
                        <tr>
                            <th>@lang('eval_reports.cols.form')</th>
                            <th>@lang('eval_reports.cols.supervisor')</th>
                            <th>@lang('eval_reports.cols.teacher')</th>
                            <th>@lang('eval_reports.cols.school')</th>
                            <th>@lang('eval_reports.cols.specialization')</th>
                            <th>@lang('eval_reports.cols.form_type')</th>
                            <th>@lang('eval_reports.cols.visit_type')</th>
                            <th>@lang('eval_reports.cols.visit_date')</th>
                            <th>@lang('eval_reports.cols.eval_date')</th>
                            <th>@lang('eval_reports.cols.total_score')</th>
                            <th>@lang('eval_reports.cols.percentage')</th>
                            <th>@lang('eval_reports.cols.status')</th>
                            <th>@lang('eval_reports.cols.teacher_viewed')</th>
                            <th>@lang('eval_reports.cols.teacher_commented')</th>
                            <th>@lang('eval_reports.cols.evidence')</th>
                            <th>@lang('eval_reports.cols.notes')</th>
                            <th>@lang('eval_reports.cols.last_update')</th>
                            <th class="text-end no-print">@lang('eval_reports.cols.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="fw-bold">{{ $r['form'] ?? '—' }}</td>
                                <td>{{ $r['supervisor'] ?? '—' }}</td>
                                <td>{{ $r['teacher'] ?? '—' }}</td>
                                <td>{{ $r['school'] ?? '—' }}</td>
                                <td>{{ $r['specialization'] ?? '—' }}</td>
                                <td>{{ $r['form_type'] ?? '—' }}</td>
                                <td>{{ $r['visit_type'] ?? '—' }}</td>
                                <td>{{ $r['visit_date'] ? \Illuminate\Support\Carbon::parse($r['visit_date'])->format('Y-m-d') : '—' }}</td>
                                <td>{{ $r['eval_date'] ? \Illuminate\Support\Carbon::parse($r['eval_date'])->format('Y-m-d') : '—' }}</td>
                                <td>{{ $r['total_score'] ?? '—' }}</td>
                                <td>{{ $r['percentage'] !== null ? $r['percentage'].'%' : '—' }}</td>
                                <td><span class="ev-pill {{ $r['status']?->value }}">{{ $r['status']?->label() }}</span></td>
                                <td>@if($r['teacher_viewed'])<span class="bool-yes"><i class="la la-check"></i></span>@else<span class="bool-no">—</span>@endif</td>
                                <td>@if($r['teacher_commented'])<span class="bool-yes"><i class="la la-check"></i></span>@else<span class="bool-no">—</span>@endif</td>
                                <td>{{ $r['evidence_count'] }}</td>
                                <td>{{ $r['notes_count'] }}</td>
                                <td><span class="text-muted small">{{ $r['last_update'] ? \Illuminate\Support\Carbon::parse($r['last_update'])->format('Y-m-d') : '—' }}</span></td>
                                <td class="text-end no-print">
                                    <a href="{{ route('admin.evaluations.approvals.show', $r['id']) }}" class="btn btn-sm btn-outline-secondary" title="@lang('eval_reports.view')"><i class="la la-eye"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($paginator->hasPages())
                <div class="card-footer no-print">{{ $paginator->links() }}</div>
            @endif
        @else
            @include('admin.evaluation.reports._empty')
        @endif
    </div>
</div>
@endsection
