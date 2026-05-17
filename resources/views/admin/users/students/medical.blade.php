@extends('layouts.app')

@section('title', __('users.medical_link').' — '.$student->name)
@section('body_class','theme-light')

@include('admin.users.students._sub_styles')

@section('content')
@php $active = 'medical'; @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $student->name }} — @lang('users.medical_link')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.show', $student->id) }}">{{ $student->name }}</a></li>
                <li class="breadcrumb-item active">@lang('users.medical_link')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('admin.users.students._header')
    @include('admin.users.students._subnav')

    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="icon-wrap"><i class="la la-notes-medical"></i></div>
                <h4>@lang('users.student_medical_empty_title')</h4>
                <p>@lang('users.student_medical_empty_desc')</p>
            </div>
        </div>
    </div>
</div>
@endsection
