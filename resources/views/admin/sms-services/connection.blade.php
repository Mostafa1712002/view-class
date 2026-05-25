@extends('layouts.app')

@php
    $schoolName = app()->getLocale() === 'en'
        ? ($school->name_en ?: $school->name_ar ?: $school->name)
        : ($school->name_ar ?: $school->name);
@endphp

@section('title', trans('sms_services.connection_title', ['school' => $schoolName]))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            {{ trans('sms_services.connection_title', ['school' => $schoolName]) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sms-services.index') }}">@lang('sms_services.breadcrumb')</a></li>
                <li class="breadcrumb-item active">@lang('sms_services.edit_connection')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    @if(empty($setting->api_key))
        <div class="alert alert-warning"><i class="la la-info-circle"></i> @lang('sms_services.configure_credentials_empty')</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.sms-services.connection.update', $school) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="provider" class="form-label">@lang('sms_services.provider')</label>
                        <input type="text" class="form-control" id="provider" name="provider" value="{{ old('provider', $setting->provider) }}">
                        <small class="text-muted">@lang('sms_services.provider_hint')</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sms_total" class="form-label">@lang('sms_services.sms_total')</label>
                        <input type="number" min="0" class="form-control" id="sms_total" name="sms_total" value="{{ old('sms_total', $setting->sms_total) }}">
                        <small class="text-muted">@lang('sms_services.sms_total_hint')</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="api_key" class="form-label">@lang('sms_services.api_key')</label>
                        <input type="text" class="form-control" id="api_key" name="api_key" value="{{ old('api_key', $setting->api_key) }}" autocomplete="off">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="api_secret" class="form-label">@lang('sms_services.api_secret')</label>
                        <input type="password" class="form-control" id="api_secret" name="api_secret" placeholder="••••••" autocomplete="new-password">
                        <small class="text-muted">@lang('sms_services.api_secret_hint')</small>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $setting->is_active))>
                            <label class="form-check-label" for="is_active">@lang('sms_services.is_active')</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('sms_services.save_settings')</button>
                <a href="{{ route('admin.sms-services.index') }}" class="btn btn-secondary"><i class="la la-times"></i> @lang('common.cancel')</a>
            </form>

            <hr>
            <form action="{{ route('admin.sms-services.connection.test', $school) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-info"><i class="la la-plug"></i> @lang('sms_services.test_connection')</button>
                <small class="text-muted d-block mt-1">@lang('sms_services.test_connection_hint')</small>
            </form>
        </div>
    </div>
</div>
@endsection
