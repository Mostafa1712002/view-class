@extends('layouts.app')

@section('title', __('question_banks.create_title'))
@section('body_class', 'theme-light')

@push('styles')
@include('admin.question-banks._form_styles')
@endpush

@section('content')
<div class="content-header row qb-header">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('question_banks.create_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('question_banks.breadcrumb_home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('question_banks.page_title')</a></li>
            <li class="breadcrumb-item active">@lang('question_banks.create_title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-3 col-12 text-md-right d-flex align-items-start justify-content-md-end gap-2 pt-1">
        <a href="{{ route('admin.question-banks.batch.create') }}" class="btn btn-sm btn-outline-secondary">
            <i class="la la-th-list"></i> @lang('question_banks.batch_create_link')
        </a>
        <a href="{{ route('admin.question-banks.index') }}" class="btn-reset">
            <i class="la la-arrow-right"></i> @lang('question_banks.form.cancel')
        </a>
    </div>
</div>

<div class="content-body">
    <div class="qb-form-wrap">
        <form action="{{ route('admin.question-banks.store') }}" method="POST" autocomplete="off">
            @include('admin.question-banks._form')
        </form>
    </div>
</div>
@endsection
