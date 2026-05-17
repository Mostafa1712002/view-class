@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.private.add_library'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('libraries.private.add_library')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.libraries.private.index') }}">@lang('libraries.private.title')</a></li>
                <li class="breadcrumb-item active">@lang('libraries.private.add_library')</li>
            </ol>
        </div>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.libraries.private.store') }}">
            @include('admin.libraries.private._form')
        </form>
    </div></div>
</div>
@endsection
