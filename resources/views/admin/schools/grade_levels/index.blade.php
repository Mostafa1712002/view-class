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
    @include('components.alerts')

    {{-- Add grade level (= section) --}}
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('schools.add_grade_level')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.schools.grade-levels.store', $school) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">@lang('schools.grade_level_name')</label>
                    <input type="text" name="name" class="form-control" placeholder="@lang('schools.grade_level_name_hint')" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('schools.stage')</label>
                    <select name="level" class="form-control" required>
                        <option value="primary">@lang('schools.stage_primary')</option>
                        <option value="intermediate">@lang('schools.stage_intermediate')</option>
                        <option value="secondary">@lang('schools.stage_secondary')</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('common.gender')</label>
                    <select name="gender" class="form-control" required>
                        <option value="male">@lang('schools.gender_male')</option>
                        <option value="female">@lang('schools.gender_female')</option>
                    </select>
                </div>
                <div class="col-md-2">
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
                            <th>#</th>
                            <th>@lang('schools.grade_level_name')</th>
                            <th>@lang('schools.stage')</th>
                            <th>@lang('common.gender')</th>
                            <th>@lang('schools.classes_count')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $section->name }}</td>
                                <td>@lang('schools.stage_'.$section->level)</td>
                                <td>@lang('schools.gender_'.$section->gender)</td>
                                <td>{{ $section->classes->count() }}</td>
                                <td>
                                    <a href="{{ route('admin.schools.grade-levels.classes', [$school, $section]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="la la-th-large"></i> @lang('schools.view_classes')
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">@lang('schools.no_grades_yet')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
