@extends('layouts.app')
@section('title', __('users.add_teacher'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.add_teacher')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.teachers.index') }}">@lang('users.teachers')</a></li>
            <li class="breadcrumb-item active">@lang('users.add_teacher')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    <form action="{{ route('admin.users.teachers.store') }}" method="POST" enctype="multipart/form-data">
        @include('admin.users.teachers._form')
    </form>
</div>
@endsection
