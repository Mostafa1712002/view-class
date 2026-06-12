@extends('layouts.app')

@section('title', __('evaluation_outcomes.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .ev-badge { display:inline-flex; align-items:center; padding:.2rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .ev-badge.draft    { background:#fef9ec; color:#92400e; }
    body.theme-light .ev-badge.approved { background:#ecfdf5; color:#047857; }
    body.theme-light .ev-empty { padding:3rem 1rem; text-align:center; }
    body.theme-light .ev-empty .icon-wrap { width:72px; height:72px; border-radius:18px; margin:0 auto 1rem; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.8rem; display:inline-flex; align-items:center; justify-content:center; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation_outcomes.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.index') }}">@lang('evaluation.forms.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('evaluation_outcomes.breadcrumb_index')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.outcomes.settings') }}" class="btn btn-outline-secondary me-1">
            <i class="la la-cog"></i> @lang('evaluation_outcomes.actions.settings')
        </a>
        <a href="{{ route('admin.evaluations.outcomes.create') }}" class="btn ev-add-btn">
            <i class="la la-plus"></i> @lang('evaluation_outcomes.actions.add')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        @if($outcomes->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>@lang('evaluation_outcomes.columns.test_name')</th>
                            <th style="width:110px;">@lang('evaluation_outcomes.columns.grade_level')</th>
                            <th style="width:90px;">@lang('evaluation_outcomes.columns.test_date')</th>
                            <th style="width:130px;">@lang('evaluation_outcomes.columns.method_used')</th>
                            <th style="width:90px;" class="text-center">@lang('evaluation_outcomes.columns.registered')</th>
                            <th style="width:90px;" class="text-center">@lang('evaluation_outcomes.columns.present')</th>
                            <th style="width:90px;" class="text-center">@lang('evaluation_outcomes.columns.absent')</th>
                            <th style="width:100px;" class="text-center">@lang('evaluation_outcomes.columns.final_average')</th>
                            <th style="width:90px;" class="text-center">@lang('evaluation_outcomes.columns.status')</th>
                            <th style="width:80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outcomes as $outcome)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.evaluations.outcomes.show', $outcome->id) }}" class="fw-600">
                                        {{ $outcome->test_name }}
                                    </a>
                                    @if($outcome->class_label)
                                        <small class="text-muted d-block">{{ $outcome->class_label }}</small>
                                    @endif
                                </td>
                                <td>{{ $outcome->grade_level ?? '—' }}</td>
                                <td>{{ $outcome->test_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @php $method = $outcome->method_used; @endphp
                                    {{ $method instanceof \App\Modules\Evaluation\Enums\OutcomeMethod ? $method->label() : $method }}
                                </td>
                                <td class="text-center">{{ $outcome->registered_count }}</td>
                                <td class="text-center text-success fw-600">{{ $outcome->present_count }}</td>
                                <td class="text-center text-danger">{{ $outcome->absent_count }}</td>
                                <td class="text-center fw-600">{{ number_format($outcome->final_average, 2) }}</td>
                                <td class="text-center">
                                    @php $status = $outcome->approval_status; @endphp
                                    <span class="ev-badge {{ $status instanceof \App\Modules\Evaluation\Enums\OutcomeApprovalStatus ? $status->value : $status }}">
                                        {{ $status instanceof \App\Modules\Evaluation\Enums\OutcomeApprovalStatus ? $status->label() : $status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.evaluations.outcomes.show', $outcome->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="la la-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($outcomes->hasPages())
                <div class="card-footer">{{ $outcomes->links() }}</div>
            @endif
        @else
            <div class="ev-empty">
                <div class="icon-wrap"><i class="la la-chart-bar"></i></div>
                <h5 class="mb-1">@lang('evaluation_outcomes.empty.heading')</h5>
                <p class="text-muted mb-3">@lang('evaluation_outcomes.empty.description')</p>
                <a href="{{ route('admin.evaluations.outcomes.create') }}" class="btn ev-add-btn">
                    <i class="la la-plus"></i> @lang('evaluation_outcomes.actions.add')
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
