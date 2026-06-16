@extends('layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
    $shareUrl = $certificate->share_token ? route('certificates.share', $certificate->share_token) : null;
@endphp

@section('title', __('certificates.preview_page.title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">@lang('certificates.preview_page.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.certificates.index') }}">@lang('certificates.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">@lang('certificates.preview_page.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card"><div class="card-content"><div class="card-body">
            <table class="table table-borderless mb-0">
                <tr><th class="text-muted">@lang('certificates.preview_page.student')</th><td>{{ optional($certificate->recipient)->name ?? '—' }}</td></tr>
                <tr><th class="text-muted">@lang('certificates.fields.title')</th><td>{{ $certificate->title }}</td></tr>
                <tr><th class="text-muted">@lang('certificates.fields.type')</th><td>{{ __('certificates.types.' . $certificate->type) }}</td></tr>
                <tr><th class="text-muted">@lang('certificates.fields.issue_date')</th><td>{{ optional($certificate->issue_date)->format('Y-m-d') }}</td></tr>
                <tr><th class="text-muted">@lang('certificates.fields.progress')</th><td>{{ $certificate->progress }}%</td></tr>
            </table>

            @if($shareUrl)
                <label class="mt-2 mb-0">@lang('certificates.preview_page.link')</label>
                <div class="input-group">
                    <input type="text" id="cert-link" class="form-control" value="{{ $shareUrl }}" readonly>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('cert-link').value)">
                            <x-svg-icon name="files" /> @lang('certificates.preview_page.copy')
                        </button>
                    </div>
                </div>
            @endif

            <div class="mt-2 d-flex gap-1">
                <a href="{{ route('admin.certificates.pdf', $certificate->id) }}" target="_blank" class="btn btn-primary">
                    <x-svg-icon name="file-earmark-pdf" /> @lang('certificates.preview_page.view')
                </a>
                <a href="{{ route('admin.certificates.send', $certificate->id) }}" class="btn btn-success ml-1">
                    <x-svg-icon name="send" /> @lang('certificates.preview_page.send')
                </a>
            </div>
        </div></div></div>
    </div>
    <div class="col-md-7">
        <div class="card"><div class="card-content"><div class="card-body p-0">
            <iframe src="{{ route('admin.certificates.pdf', $certificate->id) }}" style="width:100%;height:520px;border:0;border-radius:6px;" title="@lang('certificates.preview_page.open_pdf')"></iframe>
        </div></div></div>
    </div>
</div>
@endsection
