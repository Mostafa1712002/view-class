@extends('layouts.app')
@section('title', __('users.edit'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.edit'): {{ $teacher->name }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.teachers.index') }}">@lang('users.teachers')</a></li>
            <li class="breadcrumb-item active">@lang('users.edit')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    <form action="{{ route('admin.users.teachers.update', $teacher->id) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.users.teachers._form')
    </form>
</div>
@endsection
