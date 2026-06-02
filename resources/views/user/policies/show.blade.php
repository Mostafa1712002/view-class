@extends('layouts.app')
@section('title', $policy->title)
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $policy->title }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('policies.my.index') }}">@lang('policies.my_title')</a></li>
            <li class="breadcrumb-item active">{{ $policy->title }}</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-left">
        <a href="{{ route('policies.my.index') }}" class="btn btn-soft btn-sm"><i class="la la-arrow-right"></i> @lang('policies.actions.back')</a>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        @if($policy->description)<p>{{ $policy->description }}</p>@endif
        <div class="d-flex flex-wrap mt-3" style="gap:.5rem;">
            @if($policy->fileUrl())
                <a href="{{ $policy->fileUrl() }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="la la-file"></i> @lang('policies.actions.open')</a>
            @endif
            @if($policy->external_url)
                <a href="{{ $policy->external_url }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="la la-external-link-alt"></i> @lang('policies.actions.open_link')</a>
            @endif
            @if(! $policy->fileUrl() && ! $policy->external_url)
                <span class="text-muted">@lang('policies.no_attachment')</span>
            @endif
        </div>
        @if($policy->fileUrl() && \Illuminate\Support\Str::endsWith(strtolower($policy->file_path), '.pdf'))
            <div class="mt-3">
                <object data="{{ $policy->fileUrl() }}#view=FitH" type="application/pdf" style="width:100%;height:70vh;border:0;border-radius:.5rem;">
                    <iframe src="{{ $policy->fileUrl() }}" style="width:100%;height:70vh;border:0;"></iframe>
                </object>
            </div>
        @endif
    </div></div>
</div>
@endsection
