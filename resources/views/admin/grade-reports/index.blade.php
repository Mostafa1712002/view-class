@extends('layouts.app')

@section('title', trans('grades_admin.reports_title'))

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
            {{ trans('grades_admin.reports_title') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('grades_admin.home')</a></li>
                <li class="breadcrumb-item active">{{ trans('grades_admin.reports_title') }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        <a href="{{ route('admin.grade-reports.monitor') }}" class="btn btn-outline-warning">
            <x-svg-icon name="bar-chart-fill" :size="16" class="me-1" /> {{ trans('grades_admin.monitor_btn') }}
        </a>
        <a href="{{ route('admin.grade-reports.create') }}" class="btn btn-primary">
            <x-svg-icon name="plus-lg" :size="16" class="me-1" /> {{ trans('grades_admin.create_report') }}
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-info border-0">
        <x-svg-icon name="info-circle-fill" :size="16" class="ic-info me-1" />
        {{ trans('grades_admin.reports_intro') }}
    </div>

    {{-- Type filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="btn-group flex-wrap">
                <a href="{{ route('admin.grade-reports.index') }}"
                   class="btn btn-sm btn-outline-{{ !request('type') ? 'primary' : 'secondary' }}">
                    {{ trans('grades_admin.filter_all') }}
                </a>
                @foreach($typeLabels as $k => $label)
                    <a href="{{ route('admin.grade-reports.index', ['type' => $k]) }}"
                       class="btn btn-sm btn-outline-{{ request('type') === $k ? 'primary' : 'secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if($reports->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <x-svg-icon name="file-earmark-text-fill" :size="40" class="ic-muted" />
                <p class="mt-3 mb-0">{{ trans('grades_admin.no_reports') }}
                    <a href="{{ route('admin.grade-reports.create') }}">{{ trans('grades_admin.create_report') }}</a>
                </p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ trans('grades_admin.report_title') }}</th>
                                <th>{{ trans('grades_admin.report_type') }}</th>
                                <th>{{ trans('grades_admin.report_status') }}</th>
                                <th>{{ trans('grades_admin.class') }}</th>
                                <th>{{ trans('grades_admin.subject') }}</th>
                                <th class="text-center">{{ trans('grades_admin.columns_count') }}</th>
                                <th>{{ trans('grades_admin.opens_at') }}</th>
                                <th>{{ trans('grades_admin.closes_at') }}</th>
                                <th class="text-center" style="width:200px;">{{ trans('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $r)
                                <tr>
                                    <td>
                                        @if($r->is_locked)
                                            <x-svg-icon name="lock-fill" :size="16" class="ic-warn me-1" title="{{ trans('grades_admin.locked') }}" />
                                        @endif
                                        <a href="{{ route('admin.grade-reports.show', $r->id) }}" class="font-weight-bold text-dark">
                                            {{ $r->title }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $typeBadges[$r->type] ?? 'secondary' }}">
                                            {{ $typeLabels[$r->type] ?? $r->type }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($r->is_active)
                                            <span class="badge badge-success">{{ trans('grades_admin.active') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ trans('grades_admin.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $r->classRoom?->name ?? '—' }}</td>
                                    <td>{{ $r->subject?->name ?? '—' }}</td>
                                    <td class="text-center">{{ $r->columns_count }}</td>
                                    <td>{{ $r->opens_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $r->closes_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-center">
                                        {{-- Control menu dropdown --}}
                                        <div class="btn-group">
                                            <a href="{{ route('admin.grade-reports.edit', $r->id) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="{{ trans('grades_admin.control_settings') }}">
                                                <x-svg-icon name="gear-fill" :size="16" />
                                            </a>
                                            <a href="{{ route('admin.grades.entry.index', ['report_id' => $r->id]) }}"
                                               class="btn btn-sm btn-outline-info"
                                               title="{{ trans('grades_admin.control_entry') }}">
                                                <x-svg-icon name="pencil-square" :size="16" />
                                            </a>
                                            <a href="{{ route('admin.grade-reports.monitor', ['class_id' => $r->class_id, 'subject_id' => $r->subject_id]) }}"
                                               class="btn btn-sm btn-outline-warning"
                                               title="{{ trans('grades_admin.control_monitor') }}">
                                                <x-svg-icon name="bar-chart-fill" :size="16" />
                                            </a>
                                            <div class="btn-group">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        data-toggle="dropdown"
                                                        aria-haspopup="true"
                                                        aria-expanded="false">
                                                    <x-svg-icon name="three-dots-vertical" :size="16" />
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-{{ $isRtl ? 'right' : 'right' }}">
                                                    <a class="dropdown-item" href="{{ route('admin.grade-reports.show', $r->id) }}">
                                                        <x-svg-icon name="eye-fill" :size="16" class="ic-info me-1" /> {{ trans('common.show') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('admin.grade-reports.transcript', $r->id) }}">
                                                        <x-svg-icon name="table" :size="16" class="ic-navy me-1" /> {{ trans('grades_admin.control_transcript') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('admin.grade-reports.notification', $r->id) }}">
                                                        <x-svg-icon name="receipt-cutoff" :size="16" class="ic-eval me-1" /> {{ trans('grades_admin.control_notification') }}
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    {{-- Lock toggle --}}
                                                    <form method="POST"
                                                          action="{{ route('admin.grade-reports.toggle-lock', $r->id) }}"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            @if($r->is_locked)
                                                                <x-svg-icon name="unlock-fill" :size="16" class="ic-success me-1" /> {{ trans('grades_admin.control_unlock') }}
                                                            @else
                                                                <x-svg-icon name="lock-fill" :size="16" class="ic-warn me-1" /> {{ trans('grades_admin.control_lock') }}
                                                            @endif
                                                        </button>
                                                    </form>
                                                    <div class="dropdown-divider"></div>
                                                    <form method="POST"
                                                          action="{{ route('admin.grade-reports.destroy', $r->id) }}"
                                                          class="d-inline"
                                                          onsubmit="return confirm('{{ trans('grades_admin.delete_confirm') }}');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <x-svg-icon name="trash3-fill" :size="16" class="me-1" /> {{ trans('grades_admin.control_delete') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($reports->hasPages())
                <div class="card-footer">{{ $reports->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
