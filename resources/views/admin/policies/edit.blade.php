@extends('layouts.app')
@section('title', __('policies.edit'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('policies.edit')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.policies.index') }}">@lang('policies.title')</a></li>
            <li class="breadcrumb-item active">@lang('policies.edit')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.policies.update', $policy->id) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('admin.policies._form')
        </form>
    </div></div>
</div>
@endsection
