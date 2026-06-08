@extends('layouts.app')

@section('title', __('evaluation.form.edit'))
@section('body_class','theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.form.edit'): {{ $form->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.index') }}">@lang('evaluation.forms.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('evaluation.form.edit')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-5 col-12 text-end mb-2">
        <a href="{{ route('admin.evaluations.items.index', $form->id) }}" class="btn btn-outline-secondary btn-sm"><i class="la la-list-ol"></i> @lang('evaluation_items.items.page_title')</a>
        <a href="{{ route('admin.evaluations.targets.index', $form->id) }}" class="btn btn-outline-secondary btn-sm"><i class="la la-users"></i> @lang('evaluation.form.actions_menu.targets')</a>
        <a href="{{ route('admin.evaluations.evaluators.index', $form->id) }}" class="btn btn-outline-secondary btn-sm"><i class="la la-user-check"></i> @lang('evaluation.form.actions_menu.evaluators')</a>
        @if (in_array($form->status?->value, ['draft','ready'], true))
            <a href="{{ route('admin.evaluations.publish.confirm', $form->id) }}" class="btn btn-success btn-sm"><i class="la la-bullhorn"></i> @lang('evaluation.form.actions_menu.publish')</a>
        @endif
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
