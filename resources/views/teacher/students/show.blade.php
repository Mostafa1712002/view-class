@extends('layouts.admin')

@section('title', __('teacher_students.show_title', ['name' => $studentModel->name]))

@section('content')
<div class="container-fluid">

    {{-- Breadcrumb / back --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('teacher.students.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="la la-arrow-right"></i>
            </a>
            <span class="h3 mb-0">
                <i class="la la-user-graduate text-primary me-2"></i>
                {{ $studentModel->name }}
            </span>
        </div>
        @if($academicYear)
            <span class="badge bg-secondary fs-6">{{ $academicYear->name }}</span>
        @endif
    </div>

    {{-- ── OVERVIEW ───────────────────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="la la-info-circle me-2"></i>@lang('teacher_students.section_overview')
                </div>
                <div class="card-body">
                    <div class="row gy-2">
                        <div class="col-md-4">
                            <span class="text-muted">@lang('teacher_students.lbl_class'):</span>
                            <strong class="ms-1">{{ $studentModel->classRoom?->name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted">@lang('teacher_students.lbl_section'):</span>
                            <strong class="ms-1">{{ $studentModel->classRoom?->section?->name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted">@lang('teacher_students.lbl_grade_avg'):</span>
                            @if($gradeAverage !== null)
                                <strong class="ms-1 {{ $gradeAverage >= 60 ? 'text-success' : 'text-danger' }}">
                                    {{ $gradeAverage }}%
                                </strong>
                            @else
                                <strong class="ms-1 text-muted">-</strong>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ATTENDANCE ──────────────────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="la la-calendar-check me-2"></i>@lang('teacher_students.section_attendance')
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-6 col-md-3">
                            <div class="card bg-light py-3">
                                <div class="h2 mb-1 text-success">{{ $attendanceStats['present'] }}</div>
                                <small>@lang('teacher_students.att_present')</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-light py-3">
                                <div class="h2 mb-1 text-danger">{{ $attendanceStats['absent'] }}</div>
                                <small>@lang('teacher_students.att_absent')</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-light py-3">
                                <div class="h2 mb-1 text-warning">{{ $attendanceStats['late'] }}</div>
                                <small>@lang('teacher_students.att_late')</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-light py-3">
                                <div class="h2 mb-1 text-{{ $attendanceStats['rate'] >= 75 ? 'success' : 'danger' }}">
                                    {{ $attendanceStats['rate'] }}%
                                </div>
                                <small>@lang('teacher_students.att_rate')</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── GRADES ──────────────────────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="la la-graduation-cap me-2"></i>@lang('teacher_students.section_grades')
                </div>
                <div class="card-body p-0">
                    @if($grades->isEmpty())
                        <div class="text-center text-muted py-4">@lang('teacher_students.no_grades')</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>@lang('teacher_students.grades_subject')</th>
                                        <th>@lang('teacher_students.grades_semester')</th>
                                        <th class="text-center">@lang('teacher_students.grades_total')</th>
                                        <th class="text-center">@lang('teacher_students.grades_letter')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grades as $grade)
                                        <tr>
                                            <td>{{ $grade->subject?->name ?? '-' }}</td>
                                            <td>{{ $grade->semester_label }}</td>
                                            <td class="text-center">
                                                <span class="fw-semibold {{ ($grade->total ?? 0) >= 60 ? 'text-success' : 'text-danger' }}">
                                                    {{ $grade->total !== null ? number_format($grade->total, 1) : '-' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ ($grade->total ?? 0) >= 60 ? 'success' : 'danger' }}">
                                                    {{ $grade->letter_grade ?? '-' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── BEHAVIOUR ───────────────────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="la la-clipboard-list me-2"></i>@lang('teacher_students.section_behavior')
                </div>
                <div class="card-body p-0">
                    @if($behaviorRecords->isEmpty())
                        <div class="text-center text-muted py-4">@lang('teacher_students.no_behavior')</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>@lang('teacher_students.beh_behavior')</th>
                                        <th>@lang('teacher_students.beh_action')</th>
                                        <th class="text-center">@lang('teacher_students.beh_points')</th>
                                        <th>@lang('teacher_students.beh_recorder')</th>
                                        <th>@lang('teacher_students.beh_date')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($behaviorRecords as $record)
                                        <tr>
                                            <td>{{ $record->behavior?->name ?? '-' }}</td>
                                            <td>{{ $record->action?->name ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ ($record->points ?? 0) >= 0 ? 'success' : 'danger' }}">
                                                    {{ $record->points >= 0 ? '+' : '' }}{{ $record->points }}
                                                </span>
                                            </td>
                                            <td>{{ $record->recorder?->name ?? '-' }}</td>
                                            <td>{{ $record->created_at?->format('Y-m-d') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── SPECIAL EDUCATION ───────────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="la la-heartbeat me-2"></i>@lang('teacher_students.section_special_ed')
                </div>
                <div class="card-body">
                    @if(! $specialEd)
                        <p class="text-muted mb-0">@lang('teacher_students.no_special_ed')</p>
                    @else
                        <div class="row gy-2 mb-3">
                            <div class="col-md-4">
                                <span class="text-muted">@lang('teacher_students.se_category'):</span>
                                <strong class="ms-1">{{ $specialEd->categoryLabel() }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">@lang('teacher_students.se_status'):</span>
                                <strong class="ms-1">{{ $specialEd->statusLabel() }}</strong>
                            </div>
                            @if($specialEd->severity)
                                <div class="col-md-4">
                                    <span class="text-muted">@lang('teacher_students.se_severity'):</span>
                                    <strong class="ms-1">{{ $specialEd->severityLabel() }}</strong>
                                </div>
                            @endif
                        </div>
                        @if($specialEd->diagnosis)
                            <p class="mb-2">
                                <span class="text-muted">@lang('teacher_students.se_diagnosis'):</span>
                                <span class="ms-1">{{ $specialEd->diagnosis }}</span>
                            </p>
                        @endif
                        @if($specialEd->notes)
                            <p class="mb-0">
                                <span class="text-muted">@lang('teacher_students.se_notes'):</span>
                                <span class="ms-1">{{ $specialEd->notes }}</span>
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── CERTIFICATES ────────────────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="la la-award me-2"></i>@lang('teacher_students.section_certificates')
                </div>
                <div class="card-body p-0">
                    @if($certificates->isEmpty())
                        <div class="text-center text-muted py-4">@lang('teacher_students.no_certificates')</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>@lang('teacher_students.cert_title')</th>
                                        <th>@lang('teacher_students.cert_type')</th>
                                        <th>@lang('teacher_students.cert_date')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($certificates as $cert)
                                        <tr>
                                            <td>{{ $cert->title }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ $cert->type }}</span>
                                            </td>
                                            <td>{{ $cert->issue_date?->format('Y-m-d') ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
