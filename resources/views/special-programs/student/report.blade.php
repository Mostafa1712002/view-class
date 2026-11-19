@extends('layouts.app')
@section('title', __('special_programs/student_report.title'))
@section('page-title', __('special_programs/student_report.title'))
@section('content')
<div class="d-flex justify-content-between mb-3">
    @if(Route::has('student.programs.index'))<a class="btn btn-outline-secondary btn-sm" href="{{ route('student.programs.index') }}">{{ __('special_programs/parent.back') }}</a>@else<span></span>@endif
    <a class="btn btn-primary btn-sm" href="{{ route('student.programs.report.pdf', $program) }}"><x-svg-icon name="download" :size="14" /> {{ __('special_programs/parent.download_pdf') }}</a>
</div>
@include('special-programs.partials.student-report', [
    'report' => $report,
    'attemptUrl' => fn ($row) => Route::has('student.programs.result') ? route('student.programs.result', $row->attempt_id) : null,
])
@endsection
