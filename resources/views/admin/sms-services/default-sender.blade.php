@extends('layouts.app')

@php
    $schoolName = app()->getLocale() === 'en'
        ? ($school->name_en ?: $school->name_ar ?: $school->name)
        : ($school->name_ar ?: $school->name);
@endphp

@section('title', trans('sms_services.default_sender_title', ['school' => $schoolName]))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            {{ trans('sms_services.default_sender_title', ['school' => $schoolName]) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sms-services.index') }}">@lang('sms_services.breadcrumb')</a></li>
                <li class="breadcrumb-item active">@lang('sms_services.edit_default_sender')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            @if($senders->isEmpty())
                <div class="alert alert-warning">
                    <p class="mb-2">@lang('sms_services.no_approved_senders')</p>
                    <p class="mb-3 text-muted">@lang('sms_services.request_sender_first')</p>
                    <a href="{{ route('admin.sms-services.senders.create', $school) }}" class="btn btn-primary btn-sm">
                        <i class="la la-plus"></i> @lang('sms_services.go_request_sender')
                    </a>
                </div>
            @else
                <form action="{{ route('admin.sms-services.default-sender.update', $school) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="default_sender_id" class="form-label">@lang('sms_services.pick_sender')</label>
                        <select class="form-control select2" id="default_sender_id" name="default_sender_id">
                            <option value="">—</option>
                            @foreach($senders as $sender)
                                <option value="{{ $sender->id }}" @selected($setting->default_sender_id == $sender->id)>
                                    {{ app()->getLocale() === 'en' ? $sender->name_en : $sender->name_ar }}
                                    ({{ $sender->name_en }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('common.save')</button>
                    <a href="{{ route('admin.sms-services.index') }}" class="btn btn-secondary"><i class="la la-times"></i> @lang('common.cancel')</a>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
