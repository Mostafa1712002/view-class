@extends('layouts.app')

@section('title', __('schools.class_details'))

@section('content')
@php $vacancies = max(0, ($class->capacity ?? 0) - $class->students_count); @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.class_details') — {{ $class->name }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.grade-levels.index', $school) }}">@lang('schools.grade_levels')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.grade-levels.classes', [$school, $section]) }}">{{ $section->name }}</a></li>
                <li class="breadcrumb-item active">{{ $class->name }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr><th style="width:30%">@lang('schools.class_name')</th><td>{{ $class->name }}</td></tr>
                    <tr><th>@lang('schools.grade_levels')</th><td>{{ $section->name }}</td></tr>
                    <tr><th>@lang('schools.grade_level_number')</th><td>{{ $class->grade_level }}</td></tr>
                    <tr><th>@lang('schools.capacity')</th><td>{{ $class->capacity }}</td></tr>
                    <tr><th>@lang('schools.vacancies')</th><td>{{ $vacancies }}</td></tr>
                    <tr><th>@lang('schools.students')</th><td>{{ $class->students_count }}</td></tr>
                    <tr><th>@lang('schools.lead_teacher')</th><td>{{ optional($class->leadTeacher)->name_ar ?? optional($class->leadTeacher)->name ?? '—' }}</td></tr>
                    <tr><th>@lang('schools.academic_year')</th><td>{{ optional($class->academicYear)->name ?? '—' }}</td></tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.schools.grade-levels.classes.edit', [$school, $section, $class]) }}" class="btn btn-primary">
                <i class="la la-edit"></i> @lang('common.edit')
            </a>
            <a href="{{ route('admin.schools.grade-levels.classes.students', [$school, $section, $class]) }}" class="btn btn-outline-info">
                <i class="la la-users"></i> @lang('schools.students')
            </a>
            <a href="{{ route('admin.schools.grade-levels.classes', [$school, $section]) }}" class="btn btn-outline-secondary">
                <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i> @lang('common.back')
            </a>
        </div>
    </div>
</div>
@endsection
