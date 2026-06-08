@extends('layouts.app')

@section('title', __('evaluation.form.edit'))
@section('body_class','theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.form.edit'): {{ $form->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.index') }}">@lang('evaluation.forms.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('evaluation.form.edit')</li>
            </ol>
        </div>
    </div>
</div>
<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.evaluations.update', $form->id) }}">
            @csrf @method('PUT')
            @include('admin.evaluation.forms._form')
        </form>
    </div></div>
</div>
@endsection
