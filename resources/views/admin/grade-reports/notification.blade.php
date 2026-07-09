@extends('layouts.app')

@section('title', trans('grades_admin.notification_title') . ' — ' . $report->title)

@section('body_class', 'theme-light')

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            <i class="la la-file-invoice text-gold"></i>
            {{ trans('grades_admin.notification_title') }} — {{ $report->title }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('grades_admin.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.index') }}">{{ trans('grades_admin.reports_title') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.show', $report->id) }}">{{ $report->title }}</a></li>
                <li class="breadcrumb-item active">{{ trans('grades_admin.notification_title') }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12">
        @if($student)
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="la la-print"></i> @lang('grades_admin.notification_print')
            </button>
        @endif
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- Visibility + publish status warnings --}}
    @if(!$report->visible_to_student)
        <div class="alert alert-warning border-0">
            <i class="la la-eye-slash"></i>
            {{ trans('grades_admin.notification_hidden_student') }}
        </div>
    @endif
    @if(!$report->visible_to_parent)
        <div class="alert alert-warning border-0">
            <i class="la la-eye-slash"></i>
            {{ trans('grades_admin.notification_hidden_parent') }}
        </div>
    @endif
    @if(!$isPublished)
        <div class="alert alert-info border-0">
            <i class="la la-clock"></i>
            {{ trans('grades_admin.notification_not_published') }}
            @if($report->opens_at) <strong>{{ $report->opens_at->format('Y-m-d') }}</strong> @endif
        </div>
    @endif

    {{-- Class + student pickers --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.grade-reports.notification', $report->id) }}" class="row g-3 align-items-end flex-wrap">
                <div class="col-md-4">
                    <label class="form-label small">{{ trans('grades_admin.class') }}</label>
                    <select name="class_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">@lang('grades_admin.pick_class')</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">{{ trans('grades_admin.notification_pick_student') }}</label>
                    <select name="student_id" class="form-control form-control-sm">
                        <option value="">@lang('grades_admin.pick')</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" @selected($selectedStudentId == $s->id)>{{ $s->name }}</option>
                        @endforeach
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

    @if(!$student)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="la la-file-invoice la-3x text-muted"></i>
                <p class="mt-3 text-muted">{{ trans('grades_admin.notification_no_student') }}</p>
            </div>
        </div>
    @else
        {{-- Grade notification document --}}
        <div class="card" id="notification-doc">
            {{-- Header --}}
            <div class="card-header bg-gold-light" style="background: linear-gradient(135deg, var(--gold-200, #f5d98e) 0%, var(--gold-100, #fef9ed) 100%);">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="card-title mb-1">
                            <i class="la la-file-invoice"></i>
                            {{ trans('grades_admin.notification_title') }}
                        </h4>
                        <p class="mb-0 text-muted small">{{ $report->title }}</p>
                    </div>
                    <div class="col-md-4 text-{{ $isRtl ? 'left' : 'right' }}">
                        @if($report->academicTerm)
                            <span class="badge badge-light border">{{ $report->academicTerm->name }}</span>
                        @endif
                        @if($report->academicYear)
                            <span class="badge badge-light border">{{ $report->academicYear->name }}</span>
                        @endif
                        @if($isPublished)
                            <span class="badge badge-success"><i class="la la-check-circle"></i> @lang('grades_admin.open')</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Student info --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <th class="text-muted" style="width:40%;">{{ trans('grades_admin.student_name') }}</th>
                                <td class="font-weight-bold">{{ $student->name }}</td>
                            </tr>
                            @if($classId)
                            <tr>
                                <th class="text-muted">{{ trans('grades_admin.class') }}</th>
                                <td>{{ optional($classes->firstWhere('id', $classId))?->name }}</td>
                            </tr>
                            @endif
                            @if($report->subject)
                            <tr>
                                <th class="text-muted">{{ trans('grades_admin.subject') }}</th>
                                <td>{{ $report->subject->name }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-md-6">
                        @php
                            $total       = 0;
                            $maxPossible = 0;
                            foreach ($columns as $col) {
                                $val = $studentValues[$col->id] ?? null;
                                if ($col->is_in_total && $col->max_score > 0 && $col->weight > 0) {
                                    $maxPossible += $col->weight;
                                    if ($val && $val->score !== null) {
                                        $total += ($val->score / $col->max_score) * $col->weight;
                                    }
                                } elseif ($val && $val->score !== null) {
                                    $total += $val->score;
                                }
                            }
                            $pct = $maxPossible > 0 ? round(($total / $maxPossible) * 100, 1) : null;
                        @endphp
                        <div class="text-center py-3">
                            <div class="h1 font-weight-bold mb-0" style="color: var(--gold-500, #b8860b);">
                                {{ $total > 0 ? round($total, 2) : '—' }}
                            </div>
                            <small class="text-muted d-block">{{ trans('grades_admin.transcript_total') }}</small>
                            @if($pct !== null && $pct > 0)
                                <div class="mt-2">
                                    <div class="progress" style="height:10px;">
                                        <div class="progress-bar {{ $pct >= 60 ? 'bg-success' : 'bg-danger' }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $pct }}%</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Grade details table --}}
                <h6 class="font-weight-bold border-bottom pb-2 mb-3">
                    <i class="la la-list-ul"></i>
                    {{ trans('grades_admin.notification_grade_details') }}
                </h6>
                @if($columns->isEmpty())
                    <p class="text-muted">{{ trans('grades_admin.no_columns') }}</p>
                @else
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ trans('grades_admin.component_title') }}</th>
                                <th class="text-center">{{ trans('grades_admin.component_max') }}</th>
                                <th class="text-center">{{ trans('grades_admin.component_weight') }}</th>
                                <th class="text-center" style="width:120px;">{{ trans('grades_admin.grades_title') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($columns as $col)
                                @php $val = $studentValues[$col->id] ?? null; @endphp
                                <tr>
                                    <td>{{ $col->title }}</td>
                                    <td class="text-center text-muted">{{ rtrim(rtrim(number_format($col->max_score, 2, '.', ''), '0'), '.') }}</td>
                                    <td class="text-center text-muted">{{ rtrim(rtrim(number_format($col->weight, 2, '.', ''), '0'), '.') }}%</td>
                                    <td class="text-center font-weight-bold {{ $val && $val->score !== null ? '' : 'text-muted' }}">
                                        @if($val && $val->score !== null)
                                            {{ rtrim(rtrim(number_format($val->score, 2, '.', ''), '0'), '.') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('admin.grade-reports.show', $report->id) }}" class="btn btn-outline-secondary">
            <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> @lang('grades_admin.back')
        </a>
    </div>
</div>

@push('styles')
<style>
@media print {
    .content-header, .content-header-right, form, .btn, nav, header, .sidebar, aside { display: none !important; }
    #notification-doc { box-shadow: none !important; }
}
</style>
@endpush
@endsection
