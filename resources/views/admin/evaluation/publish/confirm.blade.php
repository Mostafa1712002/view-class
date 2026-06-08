@extends('layouts.app')

@section('title', __('evaluation.publish.confirm_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .ev-summary-list { list-style:none; padding:0; margin:0; }
    body.theme-light .ev-summary-list li { display:flex; justify-content:space-between; padding:.6rem .2rem; border-bottom:1px solid #f1f5f9; }
    body.theme-light .ev-summary-list li:last-child { border-bottom:0; }
    body.theme-light .ev-summary-list .k { color:#64748b; font-weight:600; }
    body.theme-light .ev-summary-list .v { font-weight:800; color:var(--gold-500); }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.publish.confirm_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.index') }}">@lang('evaluation.forms.page_title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.edit', $form->id) }}">{{ $form->title }}</a></li>
                <li class="breadcrumb-item active">@lang('evaluation.form.actions_menu.publish')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.edit', $form->id) }}" class="btn btn-outline-secondary"><i class="la la-arrow-right"></i> @lang('evaluation.publish.back')</a>
    </div>
</div>

<div class="content-body">
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="row justify-content-center">
        <div class="col-lg-7 col-12">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">@lang('evaluation.publish.subtitle')</p>

                    @if (!empty($problems))
                        <div class="alert alert-danger">
                            <h6 class="alert-heading"><i class="la la-exclamation-circle"></i> @lang('evaluation.publish.blocked_title')</h6>
                            <ul class="mb-0">@foreach ($problems as $p)<li>{{ $p }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <h6 class="fw-bold mb-2">@lang('evaluation.publish.summary_title')</h6>
                    <ul class="ev-summary-list mb-3">
                        <li><span class="k">@lang('evaluation.publish.labels.name')</span><span class="v">{{ $form->title }}</span></li>
                        <li><span class="k">@lang('evaluation.publish.labels.items')</span><span class="v">{{ $summary['items'] }}</span></li>
                        <li><span class="k">@lang('evaluation.publish.labels.indicators')</span><span class="v">{{ $summary['indicators'] }}</span></li>
                        <li><span class="k">@lang('evaluation.publish.labels.targets')</span><span class="v">{{ $summary['targets'] }}</span></li>
                        <li><span class="k">@lang('evaluation.publish.labels.evaluators')</span><span class="v">{{ $summary['evaluators'] }}</span></li>
                        <li><span class="k">@lang('evaluation.publish.labels.close_date')</span><span class="v">{{ $summary['close_date']?->format('Y-m-d') ?? __('evaluation.publish.labels.no_close') }}</span></li>
                        <li><span class="k">@lang('evaluation.publish.labels.notify')</span><span class="v">{{ $summary['notify'] ? __('evaluation.publish.labels.yes') : __('evaluation.publish.labels.no') }}</span></li>
                    </ul>

                    <form method="POST" action="{{ route('admin.evaluations.publish', $form->id) }}" onsubmit="return confirm('@lang('evaluation.publish.confirm_title')')">
                        @csrf
                        <button type="submit" class="btn ev-add-btn w-100" @disabled(!empty($problems))>
                            <i class="la la-bullhorn"></i> @lang('evaluation.publish.publish_btn')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
