@extends('layouts.app')

@section('title', __('sprint4.question_banks.edit_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.question_banks.edit_title') — {{ $bank->name_ar }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('sprint4.question_banks.index_title')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.question_banks.edit_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.question-banks.update', $bank->id) }}" method="POST">
                @method('PUT')
                @include('admin.question-banks._form')
            </form>
        </div>
    </div>
</div>
@endsection
