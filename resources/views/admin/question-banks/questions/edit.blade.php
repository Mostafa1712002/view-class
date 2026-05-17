@extends('layouts.app')

@section('title', __('questions.edit_title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $bank->name_ar }} — @lang('questions.edit_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('questions.breadcrumb.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('questions.breadcrumb.banks')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.questions.index', $bank->id) }}">{{ $bank->name_ar }}</a></li>
                <li class="breadcrumb-item active">@lang('questions.edit_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card">
        <div class="card-body">
            @include('admin.question-banks.questions._form', ['bank' => $bank, 'question' => $question, 'lessons' => $lessons])
        </div>
    </div>
</div>
@endsection
