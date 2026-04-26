@extends('layouts.app')

@section('title', __('schools.students_in_class'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            {{ $class->name }} — @lang('schools.students')
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

    {{-- Add student to class --}}
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('schools.add_student_to_class')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.schools.grade-levels.classes.students.add', [$school, $section, $class]) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-9">
                    <label class="form-label">@lang('schools.choose_student')</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">—</option>
                        @foreach($availableStudents as $st)
                            <option value="{{ $st->id }}">{{ $st->name_ar ?: $st->name }} ({{ $st->username ?? $st->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100"><i class="la la-plus"></i> @lang('common.create')</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Transfer students --}}
    <form action="{{ route('admin.schools.grade-levels.classes.students.transfer', [$school, $section, $class]) }}" method="POST">
        @csrf

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">@lang('schools.students')</h5>
                <div class="d-flex flex-wrap gap-2">
                    <select name="target_class_id" class="form-control form-control-sm" required style="width:auto;">
                        <option value="">@lang('schools.transfer_to')...</option>
                        @foreach($otherClasses as $oc)
                            <option value="{{ $oc->id }}">{{ optional($oc->section)->name }} / {{ $oc->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-warning"><i class="la la-exchange-alt"></i> @lang('schools.transfer_selected')</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="check-all"></th>
                                <th>@lang('common.id')</th>
                                <th>@lang('common.name')</th>
                                <th>@lang('schools.grade_level_number')</th>
                                <th>@lang('common.section')</th>
                                <th>@lang('common.gender')</th>
                                <th>@lang('common.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $st)
                                <tr>
                                    <td><input type="checkbox" name="student_ids[]" value="{{ $st->id }}" class="row-check"></td>
                                    <td>{{ $st->username ?? $st->id }}</td>
                                    <td>{{ $st->name_ar ?: $st->name }}</td>
                                    <td>{{ $class->grade_level }}</td>
                                    <td>{{ $section->name }}</td>
                                    <td>@lang('schools.gender_'.($st->gender ?? 'male'))</td>
                                    <td>
                                        <a href="{{ route('manage.users.edit', $st) }}" class="btn btn-sm btn-outline-warning"><i class="la la-pen"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">@lang('schools.no_students_in_class')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">{{ $students->links() }}</div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('check-all')?.addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
});
</script>
@endsection
