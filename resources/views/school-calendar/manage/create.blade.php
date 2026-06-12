@extends('layouts.app')

@section('title', __('school_calendar.create_title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('school_calendar.create_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('school_calendar.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.school-calendar.index') }}">@lang('school_calendar.title')</a></li>
                <li class="breadcrumb-item active">@lang('school_calendar.create_title')</li>
            </ol>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-content collapse show">
        <div class="card-body">
            <form action="{{ route('manage.school-calendar.store') }}" method="POST">
                @csrf
                @include('school-calendar.manage._form', ['event' => null])
                <div class="mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('school_calendar.btn_save')
                    </button>
                    <a href="{{ route('manage.school-calendar.index') }}" class="btn btn-secondary">
                        @lang('school_calendar.btn_cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
