@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('behavior.groups.add'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('behavior.groups.add')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.behavior.groups.index', ['tab' => $tab]) }}">@lang('behavior.groups.title')</a></li>
            <li class="breadcrumb-item active">@lang('behavior.groups.add')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.behavior.groups.store') }}">
            @include('admin.behavior.groups._form')
        </form>
    </div></div>
</div>
@endsection
