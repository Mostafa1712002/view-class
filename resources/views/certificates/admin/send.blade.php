@extends('layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
    $shareUrl = $certificate->share_token ? route('certificates.share', $certificate->share_token) : '';
    $msg = __('certificates.send_page.default_message', [
        'student' => optional($certificate->recipient)->name ?? '',
        'link'    => $shareUrl,
    ]);
    $channelKeys = ['in_platform', 'email', 'sms', 'whatsapp'];
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

    <form method="POST" action="{{ route('admin.certificates.send.store', $certificate->id) }}">
        @csrf

        <div class="form-group">
            <label class="d-block">@lang('certificates.send_page.channels')</label>
            @foreach($channelKeys as $ch)
                <div class="custom-control custom-checkbox custom-control-inline mb-1">
                    <input type="checkbox" class="custom-control-input" id="ch_{{ $ch }}"
                           name="channels[]" value="{{ $ch }}" {{ $ch === 'in_platform' ? 'checked' : '' }}>
                    <label class="custom-control-label" for="ch_{{ $ch }}">@lang('certificates.send_page.' . $ch)</label>
                </div>
            @endforeach
        </div>

        <div class="form-group">
            <label>@lang('certificates.send_page.message')</label>
            <textarea class="form-control" name="message" rows="4" required>{{ old('message', $msg) }}</textarea>
        </div>

        <div class="form-group">
            <label>@lang('certificates.preview_page.link')</label>
            <div class="input-group">
                <input type="text" id="cert-share-link" class="form-control" value="{{ $shareUrl }}" readonly>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary" onclick="ctCopyLink()">
                        @lang('certificates.send_page.copy_link')
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary">@lang('certificates.send_page.submit')</button>
            <a href="{{ route('admin.certificates.preview', $certificate->id) }}" class="btn btn-secondary ml-1">
                <x-svg-icon :name="$isRtl ? 'arrow-right' : 'arrow-left'" /> @lang('certificates.tpl.back')
            </a>
        </div>
    </form>
</div></div></div>
@endsection

@push('scripts')
<script>
    function ctCopyLink() {
        var el = document.getElementById('cert-share-link');
        if (!el || !el.value) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(el.value);
        } else {
            el.select();
            try { document.execCommand('copy'); } catch (e) {}
        }
    }
</script>
@endpush
