@extends('layouts.app')

@section('title', __('evaluation.my.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .me-tab .nav-link.active { background:linear-gradient(135deg,var(--gold-200),var(--gold-500)); color:#fff; border:none; }
    body.theme-light .me-progress { height:8px; border-radius:6px; background:#eef0f3; overflow:hidden; }
    body.theme-light .me-progress > span { display:block; height:100%; background:linear-gradient(135deg,var(--gold-200),var(--gold-500)); }
    body.theme-light .me-empty { padding:2.5rem 1rem; text-align:center; color:#94a3b8; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.my.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('evaluation.my.page_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <p class="text-muted">@lang('evaluation.my.subtitle')</p>

    <ul class="nav nav-pills mb-3 me-tab" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" data-toggle="tab" href="#tab-required">@lang('evaluation.my.tabs.required') <span class="badge bg-secondary">{{ count($required) }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" data-toggle="tab" href="#tab-results">@lang('evaluation.my.tabs.results') <span class="badge bg-secondary">{{ count($myResults) }}</span></a></li>
    </ul>

    <div class="tab-content">
        {{-- (a) Required of me --}}
        <div class="tab-pane fade show active" id="tab-required" role="tabpanel">
            <div class="card">
                <div class="card-header fw-bold">@lang('evaluation.my.required.title')</div>
                @if (count($required))
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr>
                                <th>@lang('evaluation.my.required.columns.form')</th>
                                <th>@lang('evaluation.my.required.columns.type')</th>
                                <th>@lang('evaluation.my.required.columns.status')</th>
                                <th class="text-center">@lang('evaluation.my.required.columns.targets')</th>
                                <th class="text-center">@lang('evaluation.my.required.columns.done')</th>
                                <th class="text-center">@lang('evaluation.my.required.columns.remaining')</th>
                                <th style="width:160px;">@lang('evaluation.my.required.columns.progress')</th>
                                <th class="text-end" style="width:130px;">@lang('evaluation.my.required.columns.actions')</th>
                            </tr></thead>
                            <tbody>
                            @foreach ($required as $r)
                                <tr>
                                    <td class="fw-bold">{{ $r['title'] }}</td>
                                    <td>{{ $r['type']?->label() }}</td>
                                    <td><span class="badge bg-info">{{ $r['status']?->label() }}</span></td>
                                    <td class="text-center">{{ $r['target_count'] }}</td>
                                    <td class="text-center text-success fw-bold">{{ $r['done'] }}</td>
                                    <td class="text-center">{{ $r['remaining'] }}</td>
                                    <td>
                                        <div class="me-progress"><span style="width: {{ $r['percent'] }}%"></span></div>
                                        <small class="text-muted">{{ $r['percent'] }}%</small>
                                    </td>
                                    <td class="text-end">
                                        @if ($r['can_start'])
                                            <a href="{{ route('admin.evaluations.subjects', $r['form_id']) }}" class="btn btn-sm btn-primary"><i class="la la-play"></i> @lang('evaluation.my.required.start')</a>
                                        @else
                                            <span class="text-muted small"><i class="la la-lock"></i></span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="me-empty"><i class="la la-clipboard-check la-2x d-block mb-2"></i>@lang('evaluation.my.required.none')</div>
                @endif
            </div>
        </div>

        {{-- (b) My results --}}
        <div class="tab-pane fade" id="tab-results" role="tabpanel">
            <div class="card">
                <div class="card-header fw-bold">@lang('evaluation.my.results.title')</div>
                @if (count($myResults))
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr>
                                <th>@lang('evaluation.my.results.columns.form')</th>
                                <th>@lang('evaluation.my.results.columns.evaluator')</th>
                                <th>@lang('evaluation.my.results.columns.status')</th>
                                <th class="text-center">@lang('evaluation.my.results.columns.percentage')</th>
                                <th>@lang('evaluation.my.results.columns.grade')</th>
                                <th>@lang('evaluation.my.results.columns.submitted')</th>
                                <th class="text-end" style="width:130px;">@lang('evaluation.my.results.columns.actions')</th>
                            </tr></thead>
                            <tbody>
                            @foreach ($myResults as $m)
                                <tr>
                                    <td class="fw-bold">{{ $m['form_title'] }}</td>
                                    <td>{{ $m['evaluator'] }}</td>
                                    <td><span class="badge bg-secondary">{{ $m['status']?->label() }}</span></td>
                                    <td class="text-center">
                                        @if ($m['can_view'] && $m['percentage'] !== null) {{ $m['percentage'] }}%
                                        @else <span class="text-muted">—</span> @endif
                                    </td>
                                    <td>{{ $m['can_view'] ? ($m['grade'] ?? '—') : '—' }}</td>
                                    <td><small class="text-muted">{{ $m['submitted']?->format('Y-m-d H:i') ?? '—' }}</small></td>
                                    <td class="text-end">
                                        @if ($m['can_view'])
                                            <a href="{{ route('admin.evaluations.execute.show', $m['id']) }}" class="btn btn-sm btn-outline-primary"><i class="la la-eye"></i> @lang('evaluation.my.results.view')</a>
                                        @else
                                            <span class="text-muted small">@lang('evaluation.my.results.hidden')</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="me-empty"><i class="la la-star la-2x d-block mb-2"></i>@lang('evaluation.my.results.none')</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
