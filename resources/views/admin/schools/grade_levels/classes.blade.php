@extends('layouts.app')

@section('title', __('schools.classes_in_grade'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            {{ $section->name }} — @lang('schools.classes')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.grade-levels.index', $school) }}">@lang('schools.grade_levels')</a></li>
                <li class="breadcrumb-item active">{{ $section->name }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('schools.add_class')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.schools.grade-levels.classes.store', [$school, $section]) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">@lang('schools.class_name')</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">@lang('schools.grade_level_number')</label>
                    <input type="number" min="1" max="12" name="grade_level" class="form-control" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">@lang('schools.capacity')</label>
                    <input type="number" min="1" max="200" name="capacity" class="form-control" value="30" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">@lang('schools.lead_teacher')</label>
                    <select name="lead_teacher_id" class="form-control">
                        <option value="">—</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->name_ar ?: $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">@lang('schools.academic_year')</label>
                    <select name="academic_year_id" class="form-control" required>
                        @foreach($academicYears as $y)
                            <option value="{{ $y->id }}" @selected($y->is_current)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100"><i class="la la-plus"></i></button>
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
                            <th>@lang('schools.class_name')</th>
                            <th>@lang('schools.grade_level_number')</th>
                            <th>@lang('schools.capacity')</th>
                            <th>@lang('schools.vacancies')</th>
                            <th>@lang('schools.students')</th>
                            <th>@lang('schools.lead_teacher')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                            @php $vacancies = max(0, ($class->capacity ?? 0) - $class->students_count); @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $class->name }}</td>
                                <td>{{ $class->grade_level }}</td>
                                <td>{{ $class->capacity }}</td>
                                <td>{{ $vacancies }}</td>
                                <td>{{ $class->students_count }}</td>
                                <td>{{ optional($class->leadTeacher)->name_ar ?? optional($class->leadTeacher)->name ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('admin.schools.grade-levels.classes.students', [$school, $section, $class]) }}" class="btn btn-sm btn-outline-info"><i class="la la-users"></i></a>
                                    <form action="{{ route('admin.schools.grade-levels.classes.destroy', [$school, $section, $class]) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">@lang('schools.no_classes_yet')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
