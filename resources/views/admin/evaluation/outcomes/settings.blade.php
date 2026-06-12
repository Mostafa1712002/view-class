@extends('layouts.app')

@section('title', __('evaluation_outcomes.settings_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-save-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1.2rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-effective-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; }
    body.theme-light .ev-effective-box .method-name { font-size:1.1rem; font-weight:700; color:var(--gold-600,#b45309); }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation_outcomes.settings_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.outcomes.index') }}">@lang('evaluation_outcomes.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">@lang('evaluation_outcomes.breadcrumb_settings')</li>
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

    <div class="row g-3">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">@lang('evaluation_outcomes.settings_title')</h5></div>
                <div class="card-body">

                    {{-- Currently effective method callout --}}
                    <div class="ev-effective-box mb-4">
                        <div class="text-muted small mb-1">@lang('evaluation_outcomes.settings.effective_label')</div>
                        <div class="method-name">
                            @php
                                $eff = \App\Modules\Evaluation\Enums\OutcomeMethod::tryFrom($effectiveMethod);
                            @endphp
                            {{ $eff ? $eff->label() : $effectiveMethod }}
                        </div>
                        <div class="text-muted small mt-1">@lang('evaluation_outcomes.settings.school_override_note')</div>
                    </div>

                    <form method="POST" action="{{ route('admin.evaluations.outcomes.settings.update') }}">
                        @csrf

                        {{-- School-level method --}}
                        @if($schoolId)
                        <div class="mb-4">
                            <label class="form-label fw-600">@lang('evaluation_outcomes.settings.school_method_label')</label>
                            <select name="school_method" class="form-select" id="school-method-select">
                                <option value="">— استخدام الإعداد العام —</option>
                                @foreach(\App\Modules\Evaluation\Enums\OutcomeMethod::options() as $val => $label)
                                    <option value="{{ $val }}" {{ $schoolMethod === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Global method — super-admin only --}}
                        @if(auth()->user()?->isSuperAdmin())
                        <div class="mb-4">
                            <label class="form-label fw-600">
                                @lang('evaluation_outcomes.settings.global_method_label')
                                <span class="badge bg-warning text-dark ms-1" style="font-size:.7rem;">مدير النظام</span>
                            </label>
                            <select name="global_method" class="form-select">
                                <option value="">— لا تغيير —</option>
                                @foreach(\App\Modules\Evaluation\Enums\OutcomeMethod::options() as $val => $label)
                                    <option value="{{ $val }}" {{ $globalMethod === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">@lang('evaluation_outcomes.settings.global_only_for_super')</div>
                        </div>
                        @else
                        <div class="alert alert-light py-2 mb-4" style="font-size:.82rem;">
                            <i class="la la-info-circle text-warning"></i>
                            @lang('evaluation_outcomes.settings.global_only_for_super')
                        </div>
                        @endif

                        <div class="alert alert-warning py-2 mb-4" style="font-size:.82rem;">
                            <i class="la la-exclamation-triangle"></i>
                            @lang('evaluation_outcomes.settings.method_warning')
                        </div>

                        <button type="submit" class="btn ev-save-btn"
                                onclick="return window.vcConfirm ? window.vcConfirm('حفظ الإعدادات؟') : true">
                            <i class="la la-save"></i> حفظ الإعدادات
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Method explainer card --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">شرح طرق الاحتساب</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="fw-600 text-dark mb-1">{{ \App\Modules\Evaluation\Enums\OutcomeMethod::AllRegistered->label() }}</div>
                        <p class="text-muted small mb-1">
                            المتوسط = مجموع الدرجات (الغائب = 0) ÷ عدد المسجلين.
                        </p>
                        <p class="text-muted small mb-0">
                            <strong>مثال:</strong> 3 طلاب، درجتان 80 و60، واحد غائب (0) → المتوسط = (80+60+0) ÷ 3 = <strong>46.67</strong>
                        </p>
                    </div>
                    <hr>
                    <div>
                        <div class="fw-600 text-dark mb-1">{{ \App\Modules\Evaluation\Enums\OutcomeMethod::AttendeesOnly->label() }}</div>
                        <p class="text-muted small mb-1">
                            المتوسط = مجموع درجات الحاضرين ÷ عدد الحاضرين فقط.
                        </p>
                        <p class="text-muted small mb-0">
                            <strong>نفس المثال:</strong> (80+60) ÷ 2 = <strong>70.00</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
