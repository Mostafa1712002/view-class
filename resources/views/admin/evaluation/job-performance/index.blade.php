@extends('layouts.app')

@section('title', __('eval_approval.jp.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-kpis .card { padding: 1rem 1.1rem; }
    body.theme-light .ev-kpis .label { color:#64748b; font-weight:600; font-size:.78rem; letter-spacing:.3px; text-transform:uppercase; margin-bottom:.35rem; }
    body.theme-light .ev-kpis .value { font-size:1.65rem; font-weight:800; color:var(--gold-400); line-height:1; }
    body.theme-light .ev-kpis .icon { width:42px; height:42px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.2rem; }
    body.theme-light .jp-chip { display:inline-block; font-size:.72rem; font-weight:600; border-radius:999px; padding:.1rem .5rem; margin:.1rem; background:#eef2f7; color:#475569; }
    body.theme-light .ev-empty { padding:3.5rem 1rem; text-align:center; }
    body.theme-light .ev-empty .icon-wrap { width:72px; height:72px; border-radius:18px; margin:0 auto 1rem; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.8rem; display:inline-flex; align-items:center; justify-content:center; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('eval_approval.jp.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('eval_approval.jp.breadcrumb')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="alert alert-info py-2"><i class="la la-info-circle"></i> @lang('eval_approval.jp.readonly_note')</div>

    {{-- KPI tiles --}}
    <div class="row ev-kpis mb-3">
        @foreach (['teachers' => 'la-chalkboard-teacher', 'evaluations' => 'la-clipboard-list', 'avg' => 'la-percentage', 'forms' => 'la-link'] as $key => $icon)
            <div class="col-md-3 col-6 mb-2">
                <div class="card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="label">@lang('eval_approval.jp.kpis.'.$key)</div>
                            <div class="value">{{ $key === 'avg' ? $stats[$key].'%' : $stats[$key] }}</div>
                        </div>
                        <span class="icon"><i class="la {{ $icon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        @if (count($teachers) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>@lang('eval_approval.jp.columns.teacher')</th>
                            <th>@lang('eval_approval.jp.columns.school')</th>
                            <th>@lang('eval_approval.jp.columns.count')</th>
                            <th>@lang('eval_approval.jp.columns.average')</th>
                            <th>@lang('eval_approval.jp.columns.latest')</th>
                            <th>@lang('eval_approval.jp.columns.effective')</th>
                            <th>@lang('eval_approval.jp.columns.status_mix')</th>
                            <th class="text-end" style="width:90px;">@lang('eval_approval.jp.columns.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teachers as $t)
                            <tr>
                                <td class="fw-bold">{{ $t['name'] }}</td>
                                <td class="text-muted small">{{ $t['school'] ?? '—' }}</td>
                                <td>{{ $t['count'] }}</td>
                                <td>{{ $t['average'] }}%</td>
                                <td>{{ $t['latest'] }}%</td>
                                <td class="fw-bold text-success">{{ $t['effective'] }}% <span class="text-muted small">({{ __('eval_approval.jp.aggregation.'.$t['mode']) }})</span></td>
                                <td>
                                    @foreach ($t['status_mix'] as $st => $n)
                                        <span class="jp-chip">{{ \App\Modules\Evaluation\Enums\EvaluationStatus::from($st)->label() }}: {{ $n }}</span>
                                    @endforeach
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.job-performance.show', $t['teacher_id']) }}" class="btn btn-sm btn-outline-secondary"><i class="la la-eye"></i> @lang('eval_approval.jp.view')</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ev-empty">
                <span class="icon-wrap"><i class="la la-briefcase"></i></span>
                <h5 class="mb-1">@lang('eval_approval.jp.empty_title')</h5>
                <p class="text-muted">@lang('eval_approval.jp.empty_subtitle')</p>
            </div>
        @endif
    </div>
</div>
@endsection
