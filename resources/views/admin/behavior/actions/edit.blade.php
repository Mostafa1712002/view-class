@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('behavior.actions_page.edit'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('behavior.actions_page.edit')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.behavior.actions.index', ['tab' => $tab]) }}">@lang('behavior.actions_page.title')</a></li>
            <li class="breadcrumb-item active">@lang('behavior.actions_page.edit')</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.behavior.actions.update', $action->id) }}">
        @method('PUT')
        @include('admin.behavior.actions._form')
    </form>
</div></div></div>
@endsection
