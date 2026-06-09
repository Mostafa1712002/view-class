@extends('layouts.app')

@section('title', trans('grades_admin.create_report'))

@section('body_class', 'theme-light')

@section('content')
@php($isRtl = app()->getLocale() === 'ar')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ trans('grades_admin.create_report') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('grades_admin.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.index') }}">{{ trans('grades_admin.reports_title') }}</a></li>
                <li class="breadcrumb-item active">{{ trans('grades_admin.create_report') }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12">
        <a href="{{ route('admin.grade-reports.index') }}" class="btn btn-outline-secondary">
            <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> {{ trans('grades_admin.back') }}
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <form method="POST" action="{{ route('admin.grade-reports.store') }}">
        @csrf
        @include('admin.grade-reports._form', ['report' => null])

        <div class="text-{{ $isRtl ? 'left' : 'right' }} mt-3">
            <a href="{{ route('admin.grade-reports.index') }}" class="btn btn-outline-secondary">
                <i class="la la-times"></i> {{ trans('grades_admin.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="la la-save"></i> {{ trans('grades_admin.save') }}
            </button>
        </div>
    </form>
</div>
@endsection
