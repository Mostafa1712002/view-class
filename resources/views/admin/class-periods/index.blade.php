@extends('layouts.app')

@section('title', __('sprint4.class_periods.page_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.class_periods.index_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.class_periods.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.class-periods.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm me-1" placeholder="@lang('sprint4.subjects.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm" type="submit"><i class="la la-search"></i></button>
        </form>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-1">
            <a class="btn btn-primary btn-sm" href="{{ route('admin.class-periods.create') }}"><i class="la la-plus"></i> @lang('sprint4.class_periods.add_btn')</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.class-periods.time-slots.index') }}"><i class="la la-clock"></i> @lang('sprint4.class_periods.manage_time_slots')</a>
            <a class="btn btn-outline-info btn-sm" href="{{ route('admin.class-periods.advanced') }}"><i class="la la-th"></i> @lang('sprint4.class_periods.advanced_schedule')</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.users.teachers.workloads') }}"><i class="la la-tasks"></i> @lang('sprint4.class_periods.teacher_workloads')</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('sprint4.class_periods.columns.teacher')</th>
                        <th>@lang('sprint4.class_periods.columns.subject')</th>
                        <th>@lang('sprint4.class_periods.columns.grade_level')</th>
                        <th>@lang('sprint4.class_periods.columns.classroom')</th>
                        <th>@lang('sprint4.class_periods.columns.substitute')</th>
                        <th>@lang('sprint4.class_periods.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $p)
                        <tr>
                            <td>{{ $p->teacher->name ?? '—' }}</td>
                            <td>{{ $p->subject->name ?? '—' }}</td>
                            <td>{{ $p->grade_level }}</td>
                            <td>{{ $p->classRoom->name ?? '—' }}</td>
                            <td>{{ $p->substituteTeacher->name ?? '—' }}</td>
                            <td>
                                <form action="{{ route('admin.class-periods.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">@lang('common.no_results')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $periods->links() }}</div>
    </div>
</div>
@endsection
