@extends('layouts.app')
@section('title', __('special_programs/reports.student_page_title'))
@section('page-title', __('special_programs/reports.student_page_title').' — '.\App\Modules\SpecialPrograms\Support\Programs::label($program))
@section('content')
<div class="mb-3">
    <a class="btn btn-light btn-sm" href="{{ route('manage.special-programs.reports.index', $program) }}">{{ __('special_programs.buttons.back') }}</a>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('manage.special-programs.student-report.pdf', [$program, $studentId]) }}"><x-svg-icon name="download" :size="14" /> {{ __('special_programs/reports.pdf_student') }}</a>
</div>
@include('special-programs.partials.student-report', ['report' => $report])
@endsection
