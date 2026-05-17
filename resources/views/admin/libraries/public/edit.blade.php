@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.actions.edit'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('libraries.actions.edit')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.libraries.public.index') }}">@lang('libraries.public.title')</a></li>
                <li class="breadcrumb-item active">{{ $item->title }}</li>
            </ol>
        </div>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.libraries.public.update', $item->id) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('admin.libraries.public._form')
        </form>
    </div></div>
</div>
@endsection
