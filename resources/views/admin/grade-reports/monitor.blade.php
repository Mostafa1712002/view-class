@extends('layouts.app')

@section('title', trans('grades_admin.monitor_title'))

@section('body_class', 'theme-light')

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            <i class="la la-chart-bar text-gold"></i>
            {{ trans('grades_admin.monitor_title') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">@lang('grades_admin.home')</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.grade-reports.index') }}">{{ trans('grades_admin.reports_title') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ trans('grades_admin.monitor_title') }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12">
        <a href="{{ route('admin.grade-reports.monitor.export', request()->query()) }}"
           class="btn btn-outline-success">
            <i class="la la-file-csv"></i> {{ trans('grades_admin.monitor_export') }}
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-info border-0">
        <i class="la la-info-circle"></i>
        {{ trans('grades_admin.monitor_intro') }}
    </div>

    {{-- Stats cards --}}
    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <div class="h2 mb-0 font-weight-bold text-dark">{{ $stats['total'] }}</div>
                    <small class="text-muted">{{ trans('grades_admin.monitor_stat_total') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border-0 shadow-sm text-center py-3" style="border-right: 4px solid #28a745 !important;">
                <div class="card-body py-2">
                    <div class="h2 mb-0 font-weight-bold text-success">{{ $stats['complete'] }}</div>
                    <small class="text-muted">{{ trans('grades_admin.monitor_stat_complete') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border-0 shadow-sm text-center py-3" style="border-right: 4px solid #ffc107 !important;">
                <div class="card-body py-2">
                    <div class="h2 mb-0 font-weight-bold text-warning">{{ $stats['missing'] }}</div>
                    <small class="text-muted">{{ trans('grades_admin.monitor_stat_missing') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card border-0 shadow-sm text-center py-3" style="border-right: 4px solid #6c757d !important;">
                <div class="card-body py-2">
                    <div class="h2 mb-0 font-weight-bold text-muted">{{ $stats['empty'] }}</div>
                    <small class="text-muted">{{ trans('grades_admin.monitor_stat_empty') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.grade-reports.monitor') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">{{ trans('grades_admin.academic_year') }}</label>
                    <select name="year_id" class="form-control form-control-sm">
                        <option value="">@lang('grades_admin.pick')</option>
                        @foreach($years as $y)
                            <option value="{{ $y->id }}" @selected(($filters['year_id'] ?? null) == $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ trans('grades_admin.academic_term') }}</label>
                    <select name="term_id" class="form-control form-control-sm">
                        <option value="">@lang('grades_admin.pick')</option>
                        @foreach($terms as $t)
                            <option value="{{ $t->id }}" @selected(($filters['term_id'] ?? null) == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ trans('grades_admin.class') }}</label>
                    <select name="class_id" class="form-control form-control-sm">
                        <option value="">@lang('grades_admin.pick')</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected(($filters['class_id'] ?? null) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ trans('grades_admin.subject') }}</label>
                    <select name="subject_id" class="form-control form-control-sm">
                        <option value="">@lang('grades_admin.pick')</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(($filters['subject_id'] ?? null) == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ trans('grades_admin.monitor_filter_status') }}</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>{{ trans('grades_admin.monitor_all') }}</option>
                        <option value="complete" @selected(($filters['status'] ?? '') === 'complete')>{{ trans('grades_admin.monitor_complete') }}</option>
                        <option value="missing" @selected(($filters['status'] ?? '') === 'missing')>{{ trans('grades_admin.monitor_missing') }}</option>
                        <option value="empty" @selected(($filters['status'] ?? '') === 'empty')>{{ trans('grades_admin.monitor_empty') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="la la-search"></i> {{ trans('grades_admin.show_table') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Results table --}}
    @if(empty($rows))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="la la-inbox la-3x text-muted"></i>
                <p class="mt-3 text-muted mb-0">{{ trans('grades_admin.monitor_no_rows') }}</p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ trans('grades_admin.monitor_report') }}</th>
                                <th>{{ trans('grades_admin.monitor_class') }}</th>
                                <th>{{ trans('grades_admin.monitor_subject') }}</th>
                                <th>{{ trans('grades_admin.monitor_teacher') }}</th>
                                <th class="text-center">{{ trans('grades_admin.monitor_enrolled') }}</th>
                                <th class="text-center">{{ trans('grades_admin.monitor_entered') }}</th>
                                <th class="text-center">{{ trans('grades_admin.monitor_missing_col') }}</th>
                                <th style="width:160px;">{{ trans('grades_admin.monitor_progress') }}</th>
                                <th class="text-center">{{ trans('grades_admin.monitor_status') }}</th>
                                <th class="text-center" style="width:100px;">{{ trans('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                @php
                                    $pct = $row['expected_count'] > 0
                                        ? round(($row['entered_count'] / $row['expected_count']) * 100)
                                        : 0;
                                    $barClass = $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                                @endphp
                                <tr>
                                    <td>
                                        @if($row['is_locked'])
                                            <i class="la la-lock text-warning" title="@lang('grades_admin.locked')"></i>
                                        @endif
                                        <a href="{{ route('admin.grade-reports.show', $row['report_id']) }}" class="font-weight-bold text-dark">
                                            {{ $row['report_title'] }}
                                        </a>
                                        @if(!$row['is_active'])
                                            <span class="badge badge-secondary badge-sm ms-1">@lang('grades_admin.inactive')</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['class_name'] }}</td>
                                    <td>{{ $row['subject_name'] }}</td>
                                    <td>
                                        <small class="text-muted">{{ $row['teacher_name'] }}</small>
                                    </td>
                                    <td class="text-center font-weight-bold">{{ $row['students_count'] }}</td>
                                    <td class="text-center text-success font-weight-bold">{{ $row['entered_count'] }}</td>
                                    <td class="text-center {{ $row['missing_count'] > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                        {{ $row['missing_count'] > 0 ? $row['missing_count'] : '—' }}
                                    </td>
                                    <td>
                                        @if($row['expected_count'] > 0)
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1" style="height:8px;">
                                                    <div class="progress-bar {{ $barClass }}" style="width:{{ $pct }}%"></div>
                                                </div>
                                                <small class="ms-2 text-muted" style="min-width:36px;">{{ $pct }}%</small>
                                            </div>
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($row['status'] === 'complete')
                                            <span class="badge badge-success">
                                                <i class="la la-check-circle"></i> @lang('grades_admin.monitor_complete')
                                            </span>
                                        @elseif($row['status'] === 'missing')
                                            <span class="badge badge-warning">
                                                <i class="la la-exclamation-triangle"></i> @lang('grades_admin.monitor_missing')
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="la la-minus-circle"></i> @lang('grades_admin.monitor_empty')
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.grades.entry.index', ['report_id' => $row['report_id'], 'class_id' => $row['class_id']]) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="{{ trans('grades_admin.monitor_entry_link') }}">
                                            <i class="la la-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
