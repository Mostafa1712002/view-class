@extends('layouts.app')

@section('title', __('appointments.schedule_edit'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('appointments.schedule_edit')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('appointments.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.appointment-schedules.index') }}">@lang('appointments.breadcrumb_schedules')</a></li>
                <li class="breadcrumb-item active">@lang('appointments.breadcrumb_edit')</li>
            </ol>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-content collapse show">
        <div class="card-body">
            <form action="{{ route('manage.appointment-schedules.update', $schedule->id) }}" method="POST">
                @csrf @method('PUT')
                @include('appointments.schedules._form', ['schedule' => $schedule])
                <div class="mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('appointments.btn_save')
                    </button>
                    <a href="{{ route('manage.appointment-schedules.index') }}" class="btn btn-secondary">
                        @lang('appointments.btn_cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
