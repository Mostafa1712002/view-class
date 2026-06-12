@extends('layouts.app')

@section('title', __('evaluation_outcomes.show_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-stat-card { border-radius:12px; padding:1rem 1.25rem; font-weight:600; }
    body.theme-light .ev-stat-card .label { font-size:.75rem; color:#64748b; font-weight:400; }
    body.theme-light .ev-stat-card .value { font-size:1.5rem; }
    body.theme-light .ev-badge { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:999px; font-size:.75rem; font-weight:600; }
    body.theme-light .ev-badge.draft    { background:#fef9ec; color:#92400e; }
    body.theme-light .ev-badge.approved { background:#ecfdf5; color:#047857; }
    body.theme-light .ev-save-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.45rem 1rem; border-radius:10px; font-weight:600; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $outcome->test_name }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.outcomes.index') }}">@lang('evaluation_outcomes.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">@lang('evaluation_outcomes.breadcrumb_show')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.outcomes.index') }}" class="btn btn-outline-secondary">
            <i class="la la-arrow-right"></i> @lang('evaluation_outcomes.actions.back_to_list')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Stats row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card ev-stat-card text-center">
                <div class="label">@lang('evaluation_outcomes.columns.registered')</div>
                <div class="value text-primary">{{ $outcome->registered_count }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ev-stat-card text-center">
                <div class="label">@lang('evaluation_outcomes.columns.present')</div>
                <div class="value text-success">{{ $outcome->present_count }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ev-stat-card text-center">
                <div class="label">@lang('evaluation_outcomes.columns.absent')</div>
                <div class="value text-danger">{{ $outcome->absent_count }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ev-stat-card text-center">
                <div class="label">@lang('evaluation_outcomes.columns.final_average')</div>
                <div class="value text-dark">{{ number_format($outcome->final_average, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Left: metadata --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th class="text-muted fw-400" style="width:45%;">@lang('evaluation_outcomes.columns.method_used')</th>
                            <td>
                                @php $method = $outcome->method_used; @endphp
                                {{ $method instanceof \App\Modules\Evaluation\Enums\OutcomeMethod ? $method->label() : $method }}
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-400">@lang('evaluation_outcomes.columns.status')</th>
                            <td>
                                @php $status = $outcome->approval_status; @endphp
                                <span class="ev-badge {{ $status instanceof \App\Modules\Evaluation\Enums\OutcomeApprovalStatus ? $status->value : $status }}">
                                    {{ $status instanceof \App\Modules\Evaluation\Enums\OutcomeApprovalStatus ? $status->label() : $status }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-400">@lang('evaluation_outcomes.columns.test_date')</th>
                            <td>{{ $outcome->test_date?->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-400">@lang('evaluation_outcomes.fields.grade_level')</th>
                            <td>{{ $outcome->grade_level ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-400">@lang('evaluation_outcomes.fields.class_label')</th>
                            <td>{{ $outcome->class_label ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-400">@lang('evaluation_outcomes.columns.source')</th>
                            <td>
                                @php $src = $outcome->source; @endphp
                                {{ $src instanceof \App\Modules\Evaluation\Enums\OutcomeSource ? $src->label() : $src }}
                            </td>
                        </tr>
                        @if($outcome->last_recomputed_at)
                        <tr>
                            <th class="text-muted fw-400">آخر إعادة احتساب</th>
                            <td>{{ $outcome->last_recomputed_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Recompute form --}}
            @if(!$outcome->isApproved() || auth()->user()?->isSuperAdmin() || auth()->user()?->isSchoolAdmin())
            <div class="card">
                <div class="card-header"><h6 class="mb-0">@lang('evaluation_outcomes.detail.recompute_heading')</h6></div>
                <div class="card-body">
                    @if($outcome->isApproved())
                        <div class="alert alert-warning py-2 mb-3" style="font-size:.82rem;">
                            <i class="la la-lock"></i> @lang('evaluation_outcomes.detail.approved_locked')
                        </div>
                    @endif

                    <div class="alert alert-warning py-2 mb-3" style="font-size:.82rem;">
                        <i class="la la-info-circle"></i>
                        @lang('evaluation_outcomes.settings.method_warning')
                    </div>

                    <form method="POST" action="{{ route('admin.evaluations.outcomes.recompute', $outcome->id) }}" id="recompute-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">@lang('evaluation_outcomes.fields.method')</label>
                            <select name="method" class="form-select form-select-sm">
                                <option value="">— @lang('evaluation_outcomes.columns.method_used') الحالية —</option>
                                @foreach($methods as $val => $label)
                                    <option value="{{ $val }}" @php
                                        $cur = $outcome->method_used;
                                        echo ($cur instanceof \App\Modules\Evaluation\Enums\OutcomeMethod ? $cur->value : $cur) === $val ? 'selected' : '';
                                    @endphp>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">@lang('evaluation_outcomes.fields.reason')</label>
                            <input type="text" name="reason" class="form-control form-control-sm" maxlength="500">
                        </div>
                        <button type="submit" class="btn ev-save-btn btn-sm w-100"
                                onclick="return window.vcConfirm ? window.vcConfirm('@lang('evaluation_outcomes.actions.recompute')') : confirm('@lang('evaluation_outcomes.actions.recompute')?')">
                            <i class="la la-redo"></i> @lang('evaluation_outcomes.actions.recompute')
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: students table --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">@lang('evaluation_outcomes.detail.students_heading')</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>@lang('evaluation_outcomes.detail.student_id')</th>
                                <th class="text-center">@lang('evaluation_outcomes.detail.score')</th>
                                <th class="text-center">@lang('evaluation_outcomes.detail.status')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($outcome->students ?? [] as $i => $s)
                            <tr class="{{ ($s['status'] ?? '') === 'absent' ? 'text-muted' : '' }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $s['student_id'] ?? '—' }}</td>
                                <td class="text-center">
                                    @if(($s['status'] ?? '') === 'absent')
                                        <span class="text-danger">غائب</span>
                                    @else
                                        {{ number_format($s['score'] ?? 0, 2) }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(($s['status'] ?? '') === 'present')
                                        <span class="badge bg-success-light text-success">@lang('evaluation_outcomes.detail.present')</span>
                                    @else
                                        <span class="badge bg-danger-light text-danger">@lang('evaluation_outcomes.detail.absent')</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
