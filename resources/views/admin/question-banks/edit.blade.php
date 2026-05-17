@extends('layouts.app')

@section('title', __('question_banks.edit_title'))
@section('body_class', 'theme-light')

@push('styles')
@include('admin.question-banks._form_styles')
@endpush

@section('content')
<div class="content-header qb-header">
    <h2>@lang('question_banks.edit_title') — {{ $bank->name_ar }}</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('question_banks.breadcrumb_home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('question_banks.page_title')</a></li>
        <li class="breadcrumb-item active">@lang('question_banks.edit_title')</li>
    </ol>
</div>

<div class="content-body">
    <div class="qb-form-wrap">
        <form action="{{ route('admin.question-banks.update', $bank->id) }}" method="POST" autocomplete="off">
            @method('PUT')
            @include('admin.question-banks._form')
        </form>
    </div>
</div>
@endsection
