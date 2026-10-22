@extends('layouts.app')

@section('title', __('users.job_titles'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@include('admin.users.job_titles._styles')

@section('content')
<div class="content-header jt-header">
    <h2>@lang('users.job_titles')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.admins.index') }}">@lang('users.admins')</a></li>
        <li class="breadcrumb-item active">@lang('users.job_titles')</li>
    </ol>
</div>

<div class="content-body">
    @include('admin.users.job_titles._panel')
</div>
@endsection
