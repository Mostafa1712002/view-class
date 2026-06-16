@extends('layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
    $shareUrl = $certificate->share_token ? route('certificates.share', $certificate->share_token) : '';
    $msg = __('certificates.send_page.default_message', [
        'student' => optional($certificate->recipient)->name ?? '',
        'link'    => $shareUrl,
    ]);
@endphp

@section('title', __('certificates.send_page.title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">@lang('certificates.send_page.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.certificates.index') }}">@lang('certificates.breadcrumb_index')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.certificates.preview', $certificate->id) }}">@lang('certificates.preview_page.title')</a></li>
                <li class="breadcrumb-item active">@lang('certificates.send_page.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="card"><div class="card-content"><div class="card-body">
    <div class="alert alert-info">{{ __('certificates.send_page.note') }}</div>

    <label>@lang('certificates.send_page.channels')</label>
    <div class="d-flex flex-wrap gap-1 mb-2">
        <span class="badge badge-secondary mr-1 p-1">@lang('certificates.send_page.sms')</span>
        <span class="badge badge-secondary mr-1 p-1">@lang('certificates.send_page.in_platform')</span>
        <span class="badge badge-secondary mr-1 p-1">@lang('certificates.send_page.email')</span>
        <span class="badge badge-secondary mr-1 p-1">@lang('certificates.send_page.whatsapp')</span>
    </div>

    <label>@lang('certificates.fields.note')</label>
    <textarea class="form-control" rows="3" readonly>{{ $msg }}</textarea>

    <div class="mt-2">
        <a href="{{ route('admin.certificates.preview', $certificate->id) }}" class="btn btn-secondary">
            <x-svg-icon :name="$isRtl ? 'arrow-right' : 'arrow-left'" /> @lang('certificates.tpl.back')
        </a>
    </div>
</div></div></div>
@endsection
