@extends('layouts.app')

@section('title', __('schools.edit_class'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.edit_class') — {{ $class->name }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.grade-levels.index', $school) }}">@lang('schools.grade_levels')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.grade-levels.classes', [$school, $section]) }}">{{ $section->name }}</a></li>
                <li class="breadcrumb-item active">@lang('common.edit')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header"><h5 class="mb-0">@lang('schools.edit_class')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.schools.grade-levels.classes.update', [$school, $section, $class]) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-3">
                    <label class="form-label">@lang('schools.class_name')</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $class->name) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">@lang('schools.grade_level_number')</label>
                    <input type="number" min="1" max="12" name="grade_level" class="form-control" value="{{ old('grade_level', $class->grade_level) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">@lang('schools.capacity')</label>
                    <input type="number" min="1" max="200" name="capacity" class="form-control" value="{{ old('capacity', $class->capacity) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('schools.lead_teacher')</label>
                    <select name="lead_teacher_id" class="form-control">
                        <option value="">—</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" @selected(old('lead_teacher_id', $class->lead_teacher_id) == $t->id)>{{ $t->name_ar ?: $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">@lang('schools.academic_year')</label>
                    <select name="academic_year_id" class="form-control" required>
                        @foreach($academicYears as $y)
                            <option value="{{ $y->id }}" @selected(old('academic_year_id', $class->academic_year_id) == $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <button class="btn btn-primary"><i class="la la-save"></i> @lang('common.save')</button>
                    <a href="{{ route('admin.schools.grade-levels.classes', [$school, $section]) }}" class="btn btn-outline-secondary">
                        <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i> @lang('common.back')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
