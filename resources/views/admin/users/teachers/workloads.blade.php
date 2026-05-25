@extends('layouts.app')
@section('title', __('users.teachers_workloads_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.teachers_workloads_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.teachers.index') }}">@lang('users.teachers')</a></li>
            <li class="breadcrumb-item active">@lang('users.workloads')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.users.teachers.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="la la-arrow-right"></i> @lang('users.teachers')
        </a>
    </div>
</div>

<div class="content-body">
    <div class="row mb-3">
        @php
            $totalTeachers = $teachers->count();
            $totalPeriods = $teachers->sum('workload_periods');
            $teachersWithLoad = $teachers->where('workload_periods', '>', 0)->count();
        @endphp
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">@lang('users.teachers')</h6>
                    <h2 class="fw-bolder mb-0">{{ $totalTeachers }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">@lang('users.workload_label')</h6>
                    <h2 class="fw-bolder mb-0">{{ $totalPeriods }}</h2>
                    <small class="text-muted">@lang('users.workload_periods_hint')</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">@lang('users.teacher_status_active')</h6>
                    <h2 class="fw-bolder mb-0">{{ $teachersWithLoad }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>@lang('users.teachers_workloads_title')</strong>
            <div class="text-muted small">{{ $totalTeachers }} @lang('users.teachers')</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.national_id')</th>
                        <th class="text-center">@lang('users.workload_label')</th>
                        <th class="text-end">@lang('users.controls')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($teachers as $t)
                    <tr>
                        <td>
                            <strong>{{ $t->name }}</strong>
                            @if($t->specialization)<br><small class="text-muted">{{ $t->specialization }}</small>@endif
                        </td>
                        <td>{{ $t->national_id ?? '—' }}</td>
                        <td class="text-center">
                            @if($t->workload_periods > 0)
                                <span class="badge bg-primary" style="font-size:0.95em">{{ $t->workload_periods }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.teachers.show', $t->id) }}" class="btn btn-sm btn-outline-info" title="@lang('users.show_details')">
                                <i class="la la-eye"></i>
                            </a>
                            <a href="{{ route('admin.users.teachers.edit', $t->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('users.edit')">
                                <i class="la la-edit"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">@lang('users.no_results')</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
