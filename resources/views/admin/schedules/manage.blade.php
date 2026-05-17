@extends('layouts.app')

@section('title', __('schedule.manage_schedules'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('schedule.manage_schedules')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.schedules.index') }}">@lang('schedule.breadcrumb')</a></li>
                        <li class="breadcrumb-item active">@lang('schedule.manage_schedules')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.schedules.index') }}" class="btn btn-outline-primary"><i data-feather="grid"></i> @lang('schedule.view_grid')</a>
        <a href="{{ route('manage.schedules.create') }}" class="btn btn-primary"><i data-feather="plus"></i> @lang('schedule.create_schedule')</a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">@lang('schedule.filters')</h4>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.class')</label>
                    <select name="class_id" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}{{ $class->division ? ' - ' . $class->division : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.academic_year')</label>
                    <select name="academic_year_id" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="form-label">@lang('schedule.semester')</label>
                    <select name="semester" class="form-control">
                        <option value="">@lang('schedule.all')</option>
                        <option value="first" {{ request('semester') === 'first' ? 'selected' : '' }}>الفصل الأول</option>
                        <option value="second" {{ request('semester') === 'second' ? 'selected' : '' }}>الفصل الثاني</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-1">@lang('schedule.search')</button>
                    <a href="{{ route('manage.schedules.list') }}" class="btn btn-outline-secondary">@lang('schedule.reset')</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('schedule.class')</th>
                        <th>@lang('schedule.section')</th>
                        <th>@lang('schedule.academic_year')</th>
                        <th>@lang('schedule.semester')</th>
                        <th>@lang('schedule.total_periods')</th>
                        <th>@lang('schedule.status')</th>
                        <th>@lang('common.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->id }}</td>
                        <td>{{ $schedule->classRoom->name }}{{ $schedule->classRoom->division ? ' - ' . $schedule->classRoom->division : '' }}</td>
                        <td>{{ optional($schedule->classRoom->section)->name ?: '-' }}</td>
                        <td>{{ optional($schedule->academicYear)->name }}</td>
                        <td>{{ $schedule->semester_label }}</td>
                        <td><span class="badge bg-light-info">{{ $schedule->periods_count }}</span></td>
                        <td>
                            @if($schedule->is_active)
                                <span class="badge bg-success">@lang('schedule.active')</span>
                            @else
                                <span class="badge bg-secondary">@lang('schedule.inactive')</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('manage.schedules.show', $schedule) }}" class="btn btn-sm btn-info" title="@lang('schedule.show_schedule')"><i data-feather="eye"></i></a>
                            <a href="{{ route('manage.schedules.edit', $schedule) }}" class="btn btn-sm btn-warning" title="@lang('schedule.edit_schedule')"><i data-feather="edit"></i></a>
                            <form action="{{ route('manage.schedules.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="@lang('schedule.delete_schedule')"><i data-feather="trash-2"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">@lang('schedule.no_results')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $schedules->links() }}
        </div>
    </div>
</div>
@endsection
