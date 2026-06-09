@extends('layouts.app')

@section('title', __('eval_approval.jp.detail_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .jp-summary .card { padding:1rem 1.1rem; }
    body.theme-light .jp-summary .label { color:#64748b; font-weight:600; font-size:.78rem; margin-bottom:.35rem; }
    body.theme-light .jp-summary .value { font-size:1.5rem; font-weight:800; color:var(--gold-400); }
    body.theme-light .ev-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .ev-pill.completed { background:#e0f2fe; color:#0369a1; }
    body.theme-light .ev-pill.approved { background:#ecfdf5; color:#047857; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('eval_approval.jp.detail_title') — {{ $teacher->name }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.job-performance.index') }}">@lang('eval_approval.jp.breadcrumb')</a></li>
                <li class="breadcrumb-item active">{{ $teacher->name }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.job-performance.index') }}" class="btn btn-outline-secondary"><i class="la la-arrow-right"></i> @lang('eval_approval.actions.back')</a>
    </div>
</div>

<div class="content-body">
    <div class="alert alert-info py-2"><i class="la la-info-circle"></i> @lang('eval_approval.jp.readonly_note')</div>

    {{-- Summary --}}
    <div class="row jp-summary mb-3">
        @foreach ([
            'count' => ['eval_approval.jp.columns.count', $summary['count']],
            'average' => ['eval_approval.jp.columns.average', $summary['average'].'%'],
            'latest' => ['eval_approval.jp.columns.latest', $summary['latest'].'%'],
            'effective' => ['eval_approval.jp.columns.effective', $summary['effective'].'%'],
        ] as $key => $pair)
            <div class="col-md-3 col-6 mb-2">
                <div class="card h-100">
                    <div class="label">@lang($pair[0])</div>
                    <div class="value">{{ $pair[1] }}</div>
                    @if ($key === 'effective')<div class="text-muted small">{{ __('eval_approval.jp.aggregation.'.$summary['mode']) }}</div>@endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        @if (count($detail) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('eval_approval.jp.detail.form')</th>
                            <th>@lang('eval_approval.jp.detail.evaluator')</th>
                            <th>@lang('eval_approval.jp.detail.percentage')</th>
                            <th>@lang('eval_approval.jp.detail.date')</th>
                            <th>@lang('eval_approval.jp.detail.status')</th>
                            <th>@lang('eval_approval.jp.detail.evidence')</th>
                            <th>@lang('eval_approval.jp.detail.weight')</th>
                            <th>@lang('eval_approval.jp.detail.count_on')</th>
                            <th>@lang('eval_approval.jp.detail.aggregation')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detail as $d)
                            <tr>
                                <td class="fw-bold">#{{ $d['id'] }}</td>
                                <td>{{ $d['form'] }}</td>
                                <td>{{ $d['evaluator'] ?? '—' }}</td>
                                <td class="fw-bold">{{ $d['percentage'] }}%</td>
                                <td class="text-muted small">{{ optional($d['date'])->format('Y-m-d') ?? '—' }}</td>
                                <td><span class="ev-pill {{ $d['status']?->value }}">{{ $d['status']?->label() }}</span></td>
                                <td>{{ $d['evidence'] }}</td>
                                <td>{{ $d['weight'] !== null ? $d['weight'] : '—' }}</td>
                                <td>{{ __('eval_approval.jp.count_on.'.($d['count_on'] === 'approve' ? 'approve' : 'submit')) }}</td>
                                <td>{{ __('eval_approval.jp.aggregation.'.($d['aggregation'] === 'last' ? 'last' : 'average')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-4 text-center text-muted">@lang('eval_approval.jp.empty_title')</div>
        @endif
    </div>
</div>
@endsection
