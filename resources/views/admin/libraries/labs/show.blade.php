@extends('layouts.app')

@section('body_class','theme-light')
@section('title', $lab->title)

@include('admin.libraries._styles')

@section('content')
<div class="lib-scope">
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $lab->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.libraries.labs.index') }}">@lang('libraries.labs.title')</a></li>
                <li class="breadcrumb-item active">{{ $lab->title }}</li>
            </ol>
        </div>
    </div>
</div>
<div class="content-body">
    <div class="card">
        @if($lab->thumbnail_path)
            <img src="{{ asset('storage/' . $lab->thumbnail_path) }}" class="card-img-top" style="max-height:300px;object-fit:cover" />
        @endif
        <div class="card-body">
            @if($lab->description)<p>{{ $lab->description }}</p>@endif
            @if($lab->external_url)
                <a href="{{ $lab->external_url }}" target="_blank" class="btn btn-primary"><i class="la la-external-link-alt"></i> @lang('libraries.labs.open')</a>
            @else
                <div class="alert alert-info">@lang('libraries.labs.coming_soon')</div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection
