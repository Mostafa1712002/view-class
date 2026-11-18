@extends('layouts.app')

@section('title', __('exams_admin.page_title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('exams_admin.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('exams_admin.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-primary">@lang('exams_admin.manage_online')</a>
    </div>
</div>

<div class="content-body">
    @include('admin.exams._schedule_tabs', ['active' => 'exam'])

    <div class="alert alert-info"><i class="la la-info-circle"></i> @lang('exams_admin.schedule_hint')</div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3 col-6"><label class="form-label" for="date_from">@lang('exams_admin.schedule_from')</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-3 col-6"><label class="form-label" for="date_to">@lang('exams_admin.schedule_to')</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-md-2 col-6"><label class="form-label" for="class_id">@lang('exams_admin.filters.class')</label>
                <select class="form-control" id="class_id" name="class_id"><option value="">—</option>
                    @foreach($classes as $class)<option value="{{ $class->id }}" @selected(($filters['class_id'] ?? null) == $class->id)>{{ $class->name }}</option>@endforeach
                </select></div>
            <div class="col-md-2 col-6"><label class="form-label" for="subject_id">@lang('exams_admin.filters.subject')</label>
                <select class="form-control" id="subject_id" name="subject_id"><option value="">—</option>
                    @foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(($filters['subject_id'] ?? null) == $subject->id)>{{ $subject->name }}</option>@endforeach
                </select></div>
            <div class="col-md-2 col-12"><button class="btn btn-primary w-100">@lang('exams_admin.schedule_filter')</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr>
                <th>@lang('exams_admin.schedule_date')</th>
                <th>@lang('exams_admin.schedule_time')</th>
                <th>@lang('exams_admin.columns.title')</th>
                <th>@lang('exams_admin.columns.subject')</th>
                <th>@lang('exams_admin.columns.class')</th>
                <th>@lang('exams_admin.columns.teacher')</th>
                <th>@lang('exams_admin.columns.type')</th>
                <th>@lang('exams_admin.columns.status')</th>
            </tr></thead>
            <tbody>
            @forelse($exams as $exam)
                <tr>
                    <td>{{ $exam->start_time->format('Y-m-d') }}</td>
                    <td>{{ $exam->start_time->format('H:i') }}@if($exam->end_time) – {{ $exam->end_time->format('H:i') }}@endif</td>
                    <td><a href="{{ route('admin.exams.show', $exam) }}">{{ $exam->title }}</a></td>
                    <td>{{ $exam->subject->name ?? '—' }}</td>
                    <td>{{ $exam->classRoom->name ?? '—' }}</td>
                    <td>{{ $exam->teacher->name ?? '—' }}</td>
                    <td>{{ $exam->type_label }}</td>
                    <td>{{ $exam->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">@lang('exams_admin.schedule_empty')</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-2">{{ $exams->links() }}</div>
</div>
@endsection
