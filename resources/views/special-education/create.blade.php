@extends('layouts.app')

@section('title', __('special_education.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('special_education.title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('special_education.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.special-education.index') }}">@lang('special_education.title')</a></li>
                <li class="breadcrumb-item active">@lang('special_education.btn_add')</li>
            </ol>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-content collapse show">
        <div class="card-body">
            <form action="{{ route('manage.special-education.store') }}" method="POST">
                @csrf

                <div class="row">
                    {{-- Student --}}
                    <div class="col-12 col-md-6 form-group">
                        <label class="required">@lang('special_education.field_student')</label>
                        <select name="student_id" class="form-control @error('student_id') is-invalid @enderror" required>
                            <option value="">@lang('special_education.select_student')</option>
                            @foreach($schoolUsers as $user)
                                <option value="{{ $user->id }}" {{ old('student_id') == $user->id ? 'selected' : '' }}>
                                    {{ $isRtl && $user->name_ar ? $user->name_ar : $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Category --}}
                    <div class="col-12 col-md-6 form-group">
                        <label class="required">@lang('special_education.field_category')</label>
                        <select name="category" class="form-control @error('category') is-invalid @enderror" required>
                            <option value="">@lang('special_education.select_category')</option>
                            @foreach(['learning_disability','gifted','speech','physical','behavioral','visual','hearing','other'] as $cat)
                                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                    @lang('special_education.category_' . $cat)
                                </option>
                            @endforeach
                        </select>
                        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Severity --}}
                    <div class="col-12 col-md-4 form-group">
                        <label>@lang('special_education.field_severity')</label>
                        <select name="severity" class="form-control @error('severity') is-invalid @enderror">
                            <option value="">@lang('special_education.select_severity')</option>
                            @foreach(['mild','moderate','severe'] as $sev)
                                <option value="{{ $sev }}" {{ old('severity') === $sev ? 'selected' : '' }}>
                                    @lang('special_education.severity_' . $sev)
                                </option>
                            @endforeach
                        </select>
                        @error('severity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status --}}
                    <div class="col-12 col-md-4 form-group">
                        <label class="required">@lang('special_education.field_status')</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                            @foreach(['active','inactive','graduated'] as $st)
                                <option value="{{ $st }}" {{ old('status', 'active') === $st ? 'selected' : '' }}>
                                    @lang('special_education.student_status_' . $st)
                                </option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Assigned specialist --}}
                    <div class="col-12 col-md-4 form-group">
                        <label>@lang('special_education.field_assigned_specialist')</label>
                        <select name="assigned_specialist" class="form-control @error('assigned_specialist') is-invalid @enderror">
                            <option value="">@lang('special_education.select_specialist')</option>
                            @foreach($schoolUsers as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_specialist') == $user->id ? 'selected' : '' }}>
                                    {{ $isRtl && $user->name_ar ? $user->name_ar : $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_specialist') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Diagnosis --}}
                    <div class="col-12 form-group">
                        <label>@lang('special_education.field_diagnosis')</label>
                        <textarea name="diagnosis" rows="3" class="form-control @error('diagnosis') is-invalid @enderror">{{ old('diagnosis') }}</textarea>
                        @error('diagnosis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Notes --}}
                    <div class="col-12 form-group">
                        <label>@lang('special_education.field_notes')</label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('special_education.btn_save')
                    </button>
                    <a href="{{ route('manage.special-education.index') }}" class="btn btn-secondary">
                        @lang('special_education.btn_back')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
