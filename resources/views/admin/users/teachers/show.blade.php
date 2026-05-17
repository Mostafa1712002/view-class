@extends('layouts.app')
@section('title', $teacher->name)
@section('body_class', 'theme-light')
@section('content')
@php $tp = $teacher->teacherProfile; @endphp

<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $teacher->name }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.teachers.index') }}">@lang('users.teachers')</a></li>
            <li class="breadcrumb-item active">@lang('users.show_details')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.users.teachers.edit', $teacher->id) }}" class="btn btn-sm btn-primary">
            <i class="la la-edit"></i> @lang('users.edit')
        </a>
        <a href="{{ route('admin.users.teachers.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="la la-arrow-right"></i> @lang('users.teachers')
        </a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    @if(!empty($tp?->profile_photo))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tp->profile_photo) }}"
                             alt="{{ $teacher->name }}"
                             class="rounded-circle mb-3"
                             style="width:120px;height:120px;object-fit:cover;" />
                    @else
                        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                             style="width:120px;height:120px;background:#e2e8f0;color:#64748b;font-size:3rem;">
                            <i class="la la-chalkboard-teacher"></i>
                        </div>
                    @endif
                    <h4 class="mb-1">{{ $teacher->name }}</h4>
                    @if($teacher->name_en)<div class="text-muted small mb-2" dir="ltr">{{ $teacher->name_en }}</div>@endif
                    @if($teacher->specialization)<div class="text-muted">{{ $teacher->specialization }}</div>@endif
                    <hr>
                    <div class="text-start">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">@lang('users.username'):</span><code>{{ $teacher->username }}</code></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">@lang('users.national_id'):</span><span>{{ $teacher->national_id ?? '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">@lang('users.employee_id'):</span><span>{{ $teacher->employee_id ?? '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">@lang('users.status'):</span>
                            @if($teacher->is_active)
                                <span class="badge bg-success">@lang('users.student_status_active')</span>
                            @else
                                <span class="badge bg-secondary">@lang('users.student_status_inactive')</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><strong>@lang('users.teacher_basic_info')</strong></div>
                <div class="card-body">
                    <div class="row g-2">
                        @php
                            $rows = [
                                ['users.first_name_ar', $tp?->first_name_ar],
                                ['users.father_name_ar', $tp?->father_name_ar],
                                ['users.grandfather_name_ar', $tp?->grandfather_name_ar],
                                ['users.family_name_ar', $tp?->family_name_ar],
                                ['users.first_name_en', $tp?->first_name_en],
                                ['users.father_name_en', $tp?->father_name_en],
                                ['users.grandfather_name_en', $tp?->grandfather_name_en],
                                ['users.family_name_en', $tp?->family_name_en],
                                ['users.passport_number', $tp?->passport_number],
                                ['users.national_id', $teacher->national_id],
                                ['users.employee_id', $teacher->employee_id],
                                ['users.specialization', $teacher->specialization],
                                ['users.qualification', $teacher->qualification],
                                ['users.gender', $teacher->gender ? __('users.gender_'.$teacher->gender) : null],
                                ['users.date_of_birth', optional($teacher->date_of_birth)->format('Y-m-d')],
                                ['users.birth_place', $tp?->birth_place],
                                ['users.hire_date', optional($teacher->hire_date)->format('Y-m-d')],
                                ['users.teacher_address', $teacher->address],
                                ['users.phone', $teacher->phone],
                                ['users.phone_secondary', $tp?->phone_secondary],
                                ['users.email', $teacher->email],
                                ['users.nationality', $tp?->nationality],
                            ];
                        @endphp
                        @foreach($rows as $r)
                            <div class="col-md-6 d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">@lang($r[0])</span>
                                <strong>{{ $r[1] ?: '—' }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><strong>@lang('users.workload_label')</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center border-end">
                            <h2 class="fw-bolder mb-0">{{ \App\Models\SchedulePeriod::where('teacher_id', $teacher->id)->count() }}</h2>
                            <small class="text-muted">@lang('users.workload_periods_hint')</small>
                        </div>
                        <div class="col-md-4 text-center border-end">
                            <h2 class="fw-bolder mb-0">{{ $teacher->subjects->count() }}</h2>
                            <small class="text-muted">@lang('users.subjects_count')</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h2 class="fw-bolder mb-0">{{ \App\Models\ClassRoom::where('lead_teacher_id', $teacher->id)->count() }}</h2>
                            <small class="text-muted">@lang('users.classes_count_label')</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
