@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('behavior.behaviors.edit'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('behavior.behaviors.edit')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.behavior.behaviors.index', ['tab' => $tab]) }}">@lang('behavior.behaviors.title')</a></li>
            <li class="breadcrumb-item active">@lang('behavior.behaviors.edit')</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.behavior.behaviors.update', $behavior->id) }}">
        @method('PUT')
        @include('admin.behavior.behaviors._form')
    </form>
</div></div></div>
@endsection
