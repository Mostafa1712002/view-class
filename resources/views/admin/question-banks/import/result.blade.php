@extends('layouts.app')
@section('title', __('question_import.result.title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('question_import.result.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('questions.breadcrumb.banks')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.questions.index', $bank->id) }}">{{ $bank->name_ar }}</a></li>
                <li class="breadcrumb-item active">@lang('question_import.result.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if ($result->failed > 0)
        <div class="alert alert-warning">
            <i class="la la-exclamation-triangle"></i> @lang('question_import.result.partial')
        </div>
    @else
        <div class="alert alert-success">
            <i class="la la-check-circle"></i> @lang('question_import.result.success')
        </div>
    @endif

    {{-- Count cards --}}
    <div class="row mb-3">
        <div class="col-md-4 col-6 mb-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted">@lang('question_import.result.total')</div>
                    <h2 class="fw-bold mb-0">{{ $result->total }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6 mb-2">
            <div class="card text-center" style="background:#ecfdf5;">
                <div class="card-body">
                    <div class="text-muted">@lang('question_import.result.imported')</div>
                    <h2 class="fw-bold mb-0" style="color:#16a34a;">{{ $result->imported }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6 mb-2">
            <div class="card text-center" style="background:#fef2f2;">
                <div class="card-body">
                    <div class="text-muted">@lang('question_import.result.failed')</div>
                    <h2 class="fw-bold mb-0" style="color:#dc2626;">{{ $result->failed }}</h2>
                </div>
            </div>
        </div>
    </div>

    {{-- Action buttons --}}
    <div class="text-center mt-3">
        @if ($result->failed > 0)
            <a href="{{ route('admin.question-banks.questions.import.errors', [$bank->id, $batch->id]) }}"
               class="btn btn-outline-danger me-2">
                <i class="la la-download"></i> @lang('question_import.result.download_errors')
            </a>
        @endif
        <a href="{{ route('admin.question-banks.questions.import.form', $bank->id) }}"
           class="btn btn-outline-primary me-2">
            <i class="la la-redo"></i> @lang('question_import.result.new_import')
        </a>
        <a href="{{ route('admin.question-banks.questions.index', $bank->id) }}"
           class="btn btn-primary">
            <i class="la la-list"></i> @lang('question_import.result.back_to_questions')
        </a>
    </div>
</div>
@endsection
