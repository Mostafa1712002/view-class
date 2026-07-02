@extends('layouts.admin')

@section('title', __('teacher_students.page_title'))

@section('content')
<div class="container-fluid">

    {{-- Header row --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="la la-users text-primary me-2"></i>
            @lang('teacher_students.page_title')
        </h1>
        @if($academicYear)
            <span class="badge bg-secondary fs-6">{{ $academicYear->name }}</span>
        @endif
    </div>

    {{-- Search form --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('teacher.students.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label mb-1">@lang('teacher_students.search_label')</label>
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        value="{{ request('q') }}"
                        placeholder="@lang('teacher_students.search_placeholder')"
                    >
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-search me-1"></i>@lang('teacher_students.search_btn')
                    </button>
                    @if(request('q'))
                        <a href="{{ route('teacher.students.index') }}" class="btn btn-outline-secondary ms-1">
                            @lang('teacher_students.clear_btn')
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Results --}}
    @if($students->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="la la-user-slash text-muted" style="font-size:3rem;"></i>
                <p class="mt-3 text-muted mb-0">@lang('teacher_students.no_students')</p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>@lang('teacher_students.col_name')</th>
                                <th>@lang('teacher_students.col_academic_no')</th>
                                <th>@lang('teacher_students.col_grade')</th>
                                <th>@lang('teacher_students.col_class')</th>
                                <th>@lang('teacher_students.col_section')</th>
                                <th>@lang('teacher_students.col_gender')</th>
                                <th>@lang('teacher_students.col_last_activity')</th>
                                <th>@lang('teacher_students.col_status')</th>
                                <th>@lang('teacher_students.att_rate')</th>
                                <th class="text-center">@lang('teacher_students.col_actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($student->avatar || $student->profile_picture)
                                                <img
                                                    src="{{ asset('storage/' . ($student->profile_picture ?? $student->avatar)) }}"
                                                    class="rounded-circle"
                                                    width="36" height="36"
                                                    alt="{{ $student->name }}"
                                                >
                                            @else
                                                <span
                                                    class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                                    style="width:36px;height:36px;font-size:.85rem;flex-shrink:0;"
                                                >
                                                    {{ mb_substr($student->name ?? '?', 0, 1) }}
                                                </span>
                                            @endif
                                            <div>
                                                <div class="fw-semibold">{{ $student->name }}</div>
                                                @if($student->name_en && $student->name_en !== $student->name)
                                                    <small class="text-muted">{{ $student->name_en }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $student->national_id ?? '-' }}</td>
                                    <td>{{ $student->classRoom?->grade_level ? $student->classRoom->grade_level_label : '-' }}</td>
                                    <td>{{ $student->classRoom?->name ?? '-' }}</td>
                                    <td>{{ $student->classRoom?->section?->name ?? '-' }}</td>
                                    <td>
                                        @if($student->gender === 'male')
                                            @lang('teacher_students.gender_male')
                                        @elseif($student->gender === 'female')
                                            @lang('teacher_students.gender_female')
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $student->last_login_at?->diffForHumans() ?? '-' }}</td>
                                    <td>
                                        @if($student->is_active)
                                            <span class="badge badge-success">@lang('teacher_students.status_active')</span>
                                        @else
                                            <span class="badge badge-secondary">@lang('teacher_students.status_inactive')</span>
                                        @endif
                                    </td>
                                    <td>{{ isset($attendanceRates[$student->id]) ? $attendanceRates[$student->id] . '%' : '-' }}</td>
                                    <td class="text-center">
                                        <a
                                            href="{{ route('teacher.students.show', $student->id) }}"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            <i class="la la-eye me-1"></i>@lang('teacher_students.view_btn')
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        @if($students->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $students->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
