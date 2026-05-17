@extends('layouts.app')

@section('body_class','theme-light')
@section('title', $library->title)

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $library->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.libraries.private.index') }}">@lang('libraries.private.title')</a></li>
                <li class="breadcrumb-item active">{{ $library->title }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-end">
        <a class="btn btn-outline-info" href="{{ route('admin.libraries.private.items', $library->id) }}"><i class="la la-list"></i> @lang('libraries.actions.view_items')</a>
    </div>
</div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.libraries.private.update', $library->id) }}">
            @method('PUT')
            @include('admin.libraries.private._form')
        </form>
    </div></div>
</div>
@endsection
