@extends('layouts.app')
@section('title', __('users.edit').' — '.$parent->name)
@section('body_class', 'theme-light')

@section('content')
<div class="content-header" style="margin-bottom:1.25rem;">
    <h2 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin-bottom:.15rem;letter-spacing:-.2px;">@lang('users.edit'): {{ $parent->name }}</h2>
    <ol class="breadcrumb" style="padding:0;margin:0;background:transparent;font-size:.85rem;">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.parents.index') }}">@lang('users.parents')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.parents.show', $parent->id) }}">{{ $parent->name }}</a></li>
        <li class="breadcrumb-item active">@lang('users.edit')</li>
    </ol>
</div>

<div class="content-body">
    <form action="{{ route('admin.users.parents.update', $parent->id) }}" method="POST">
        @method('PUT')
        @include('admin.users.parents._form')
    </form>
</div>
@endsection
