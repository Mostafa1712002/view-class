@extends('layouts.app')

@php
    $schoolName = app()->getLocale() === 'en'
        ? ($school->name_en ?: $school->name_ar ?: $school->name)
        : ($school->name_ar ?: $school->name);
@endphp

@section('title', trans('sms_services.request_title', ['school' => $schoolName]))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            {{ trans('sms_services.request_title', ['school' => $schoolName]) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.sms-services.index') }}">@lang('sms_services.breadcrumb')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sms-services.senders.index', $school) }}">@lang('sms_services.view_senders')</a></li>
                <li class="breadcrumb-item active">@lang('sms_services.request_sender_name')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.sms-services.senders.store', $school) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name_en" class="form-label">@lang('sms_services.name_en') <span class="text-danger">*</span></label>
                        <input type="text" maxlength="11" class="form-control @error('name_en') is-invalid @enderror" id="name_en" name="name_en" value="{{ old('name_en') }}" required>
                        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">@lang('sms_services.name_en_hint')</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="name_ar" class="form-label">@lang('sms_services.name_ar') <span class="text-danger">*</span></label>
                        <input type="text" maxlength="11" class="form-control @error('name_ar') is-invalid @enderror" id="name_ar" name="name_ar" value="{{ old('name_ar') }}" required>
                        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">@lang('sms_services.name_ar_hint')</small>
                    </div>
                </div>

                <hr>

                <h5 class="mb-2">@lang('sms_services.attachments_title')</h5>
                <p class="text-muted small">@lang('sms_services.attachments_hint')</p>

                @foreach(['stc' => 'STC', 'mobily' => 'Mobily', 'zain' => 'Zain'] as $key => $label)
                    <div class="row align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label">@lang('sms_services.attachment_provider')</label>
                            <input type="text" class="form-control" value="{{ $label }}" readonly>
                            <input type="hidden" name="providers[]" value="{{ $key }}">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">@lang('sms_services.attachment_file')</label>
                            <input type="file" class="form-control" name="attachments[]" accept=".pdf,image/*">
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary"><i class="la la-paper-plane"></i> @lang('sms_services.submit_request')</button>
                <a href="{{ route('admin.sms-services.senders.index', $school) }}" class="btn btn-secondary"><i class="la la-times"></i> @lang('common.cancel')</a>
            </form>
        </div>
    </div>
</div>
@endsection
