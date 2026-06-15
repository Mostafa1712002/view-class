@extends('layouts.app')

@section('title', $report->title)

@section('body_class', 'theme-light')

@section('content')
@php($isRtl = app()->getLocale() === 'ar')
@php($typeLabels = [
    'dynamic'      => trans('grades_admin.type_dynamic'),
    'static'       => trans('grades_admin.type_static'),
    'gradesheet'   => trans('grades_admin.type_gradesheet'),
    'transcript'   => trans('grades_admin.type_transcript'),
    'notification' => trans('grades_admin.type_notification'),
])
@php($typeBadges = [
    'dynamic'      => 'primary',
    'static'       => 'info',
    'gradesheet'   => 'success',
    'transcript'   => 'purple',
    'notification' => 'warning',
])
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $report->title }}
            <span class="badge badge-{{ $typeBadges[$report->type] ?? 'secondary' }} ml-1">
                {{ $typeLabels[$report->type] ?? $report->type }}
            </span>
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('grades_admin.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.index') }}">{{ trans('grades_admin.reports_title') }}</a></li>
                <li class="breadcrumb-item active">{{ $report->title }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        <a href="{{ route('admin.grade-reports.edit', $report->id) }}" class="btn btn-outline-primary btn-sm" title="{{ trans('grades_admin.control_settings') }}">
            <x-svg-icon name="gear-fill" :size="16" />
        </a>
        <a href="{{ route('admin.grades.entry.index', ['report_id' => $report->id]) }}" class="btn btn-outline-info btn-sm" title="{{ trans('grades_admin.control_entry') }}">
            <x-svg-icon name="pencil-square" :size="16" />
        </a>
        <a href="{{ route('admin.grade-reports.transcript', $report->id) }}" class="btn btn-outline-secondary btn-sm" title="{{ trans('grades_admin.control_transcript') }}">
            <x-svg-icon name="table" :size="16" />
        </a>
        <a href="{{ route('admin.grade-reports.notification', $report->id) }}" class="btn btn-outline-secondary btn-sm" title="{{ trans('grades_admin.control_notification') }}">
            <x-svg-icon name="receipt-cutoff" :size="16" />
        </a>
        <a href="{{ route('admin.grade-reports.monitor', ['class_id' => $report->class_id, 'subject_id' => $report->subject_id]) }}" class="btn btn-outline-warning btn-sm" title="{{ trans('grades_admin.control_monitor') }}">
            <x-svg-icon name="bar-chart-fill" :size="16" />
        </a>
        <a href="{{ route('admin.grade-reports.index') }}" class="btn btn-outline-secondary btn-sm">
            @if($isRtl)
                <x-svg-icon name="arrow-right" :size="16" />
            @else
                <x-svg-icon name="arrow-left" :size="16" />
            @endif
            {{ trans('grades_admin.back') }}
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- Report metadata card --}}
    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-title mb-0">
                {{ trans('grades_admin.report_title') }}
                @if($report->is_locked)
                    <x-svg-icon name="lock-fill" :size="16" class="ic-warn ms-1" title="{{ trans('grades_admin.locked') }}" />
                @endif
                @if($report->is_active)
                    <span class="badge badge-success ml-1">{{ trans('grades_admin.active') }}</span>
                @else
                    <span class="badge badge-secondary ml-1">{{ trans('grades_admin.inactive') }}</span>
                @endif
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <strong>{{ trans('grades_admin.academic_year') }}:</strong>
                    {{ $report->academicYear?->name ?? '—' }}
                </div>
                <div class="col-md-4 mb-2">
                    <strong>{{ trans('grades_admin.academic_term') }}:</strong>
                    {{ $report->academicTerm?->name ?? '—' }}
                </div>
                <div class="col-md-4 mb-2">
                    <strong>{{ trans('grades_admin.created_by') }}:</strong>
                    {{ $report->creator?->name ?? '—' }}
                </div>
                <div class="col-md-4 mb-2">
                    <strong>{{ trans('grades_admin.class') }}:</strong>
                    {{ $report->classRoom?->name ?? '—' }}
                </div>
                <div class="col-md-4 mb-2">
                    <strong>{{ trans('grades_admin.subject') }}:</strong>
                    {{ $report->subject?->name ?? '—' }}
                </div>
                <div class="col-md-4 mb-2">
                    <strong>{{ trans('grades_admin.grade_input_window') }}:</strong>
                    {{ $report->grade_input_starts_at?->format('Y-m-d') ?? '—' }}
                    → {{ $report->grade_input_ends_at?->format('Y-m-d') ?? '—' }}
                </div>
                <div class="col-md-4 mb-2">
                    <strong>{{ trans('grades_admin.opens_at') }}:</strong>
                    {{ $report->opens_at?->format('Y-m-d') ?? '—' }}
                </div>
                <div class="col-md-4 mb-2">
                    <strong>{{ trans('grades_admin.closes_at') }}:</strong>
                    {{ $report->closes_at?->format('Y-m-d') ?? '—' }}
                </div>
                <div class="col-12 mt-2">
                    <strong>{{ trans('grades_admin.visibility_section') }}:</strong>
                    @if($report->visible_to_student)
                        <span class="badge badge-success ms-1"><x-svg-icon name="person-fill" :size="14" class="me-1" /> {{ trans('grades_admin.visible_to_student') }}</span>
                    @endif
                    @if($report->visible_to_parent)
                        <span class="badge badge-success ms-1"><x-svg-icon name="people-fill" :size="14" class="me-1" /> {{ trans('grades_admin.visible_to_parent') }}</span>
                    @endif
                    @if($report->visible_to_teacher)
                        <span class="badge badge-success ms-1"><x-svg-icon name="person-video3" :size="14" class="me-1" /> {{ trans('grades_admin.visible_to_teacher') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Columns card --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                {{ trans('grades_admin.components_title') }}
                <span class="badge badge-secondary ml-1">{{ $report->columns->count() }}</span>
            </h4>
        </div>
        <div class="card-body p-0">
            @if($report->columns->isEmpty())
                <div class="text-center py-4 text-muted">
                    <x-svg-icon name="columns-gap" :size="24" class="ic-muted d-block mb-2" />
                    {{ trans('grades_admin.no_columns') }}
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>{{ trans('grades_admin.component_title') }}</th>
                                <th class="text-center" style="width:120px;">{{ trans('grades_admin.component_weight') }}</th>
                                <th class="text-center" style="width:120px;">{{ trans('grades_admin.component_max') }}</th>
                                <th class="text-center" style="width:100px;">{{ trans('grades_admin.component_in_total') }}</th>
                                <th class="text-center" style="width:100px;">{{ trans('grades_admin.component_visible') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->columns as $col)
                                <tr>
                                    <td class="text-center text-muted">{{ $col->sort_order }}</td>
                                    <td>{{ $col->title }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format($col->weight, 2, '.', ''), '0'), '.') }}%</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format($col->max_score, 2, '.', ''), '0'), '.') }}</td>
                                    <td class="text-center">
                                        @if($col->is_in_total)
                                            <x-svg-icon name="check2" :size="16" class="ic-success" />
                                        @else
                                            <x-svg-icon name="x-lg" :size="16" class="ic-muted" />
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($col->is_visible)
                                            <x-svg-icon name="eye-fill" :size="16" class="ic-info" />
                                        @else
                                            <x-svg-icon name="eye-slash-fill" :size="16" class="ic-muted" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <div class="card-footer text-{{ $isRtl ? 'right' : 'left' }}">
            <a href="{{ route('admin.grade-reports.edit', $report->id) }}" class="btn btn-outline-primary btn-sm">
                <x-svg-icon name="pencil-square" :size="16" class="me-1" /> {{ trans('grades_admin.control_settings') }}
            </a>
        </div>
    </div>
</div>
@endsection
