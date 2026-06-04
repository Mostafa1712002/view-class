@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('behavior.groups.edit'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('behavior.groups.edit')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.behavior.groups.index', ['tab' => $tab]) }}">@lang('behavior.groups.title')</a></li>
            <li class="breadcrumb-item active">@lang('behavior.groups.edit')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.behavior.groups.update', $group->id) }}">
            @method('PUT')
            @include('admin.behavior.groups._form')
        </form>
    </div></div>
</div>
@endsection
