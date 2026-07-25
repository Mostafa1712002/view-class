@extends('layouts.app')

@section('title', __('schools.grade_levels'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.grade_levels') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item active">@lang('schools.grade_levels')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php $unmapped = $sections->whereNull('grade_number'); @endphp
    @if($unmapped->count())
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span><i class="la la-exclamation-triangle"></i> @lang('schools.needs_standardization', ['count' => $unmapped->count()])</span>
            <a href="{{ route('admin.schools.standardization.index', $school) }}" class="btn btn-sm btn-warning">
                @lang('schools.standardize_now')
            </a>
        </div>
    @endif

    {{-- Add grade level (= section) — picked from the fixed standard list --}}
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('schools.add_grade_level')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.schools.grade-levels.store', $school) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label">@lang('schools.grade_type')</label>
                    <select name="grade_number" class="form-control" required>
                        <option value="">— @lang('schools.grade_type') —</option>
                        @foreach($standardGrades as $ordinal => $grade)
                            <option value="{{ $ordinal }}">{{ $ordinal }} — {{ $grade['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100"><i class="la la-plus"></i> @lang('common.create')</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>@lang('schools.grade_level_number')</th>
                            <th>@lang('schools.grade_level_name')</th>
                            <th>@lang('schools.stage')</th>
                            <th>@lang('schools.classes_count')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                            <tr>
                                <td>
                                    @if($section->grade_number)
                                        <span class="badge badge-primary">{{ $section->grade_number }}</span>
                                    @else
                                        <span class="badge badge-warning">—</span>
                                    @endif
                                </td>
                                <td>{{ $section->name }}</td>
                                <td>@lang('schools.stage_'.$section->level)</td>
                                <td>{{ $section->classes->count() }}</td>
                                <td>
                                    <a href="{{ route('admin.schools.grade-levels.classes', [$school, $section]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="la la-th-large"></i> @lang('schools.view_classes')
                                    </a>
                                    <a href="{{ route('manage.books.grades', ['school' => $school->id]) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="la la-book"></i> @lang('schools.books')
                                    </a>
                                    {{-- Map an existing grade to a standard entry (assigns ordinal, keeps classes/students) --}}
                                    <form action="{{ route('admin.schools.grade-levels.update', [$school, $section]) }}" method="POST" class="d-inline-flex align-items-center ml-1">
                                        @csrf
                                        @method('PUT')
                                        <select name="grade_number" class="form-control form-control-sm d-inline-block" style="width:auto" required>
                                            <option value="">@lang('schools.map_to_standard')</option>
                                            @foreach($standardGrades as $ordinal => $grade)
                                                <option value="{{ $ordinal }}" @selected($section->grade_number === $ordinal)>{{ $ordinal }} — {{ $grade['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-success ml-1" title="@lang('common.save')"><i class="la la-save"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">@lang('schools.no_grades_yet')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
