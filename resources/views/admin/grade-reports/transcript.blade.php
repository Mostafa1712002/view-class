@extends('layouts.app')

@section('title', trans('grades_admin.transcript_title') . ' — ' . $report->title)

@section('body_class', 'theme-light')

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            <i class="la la-table text-gold"></i>
            {{ trans('grades_admin.transcript_title') }} — {{ $report->title }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('grades_admin.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.index') }}">{{ trans('grades_admin.reports_title') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.show', $report->id) }}">{{ $report->title }}</a></li>
                <li class="breadcrumb-item active">{{ trans('grades_admin.transcript_title') }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="la la-print"></i> @lang('grades_admin.notification_print')
        </button>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- Report info banner --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge badge-primary">{{ trans('grades_admin.type_' . $report->type) }}</span>
                    @if($report->is_locked) <span class="badge badge-warning"><i class="la la-lock"></i> @lang('grades_admin.locked')</span> @endif
                    @if($report->academicTerm) <span class="text-muted ms-2"><i class="la la-calendar"></i> {{ $report->academicTerm->name }}</span> @endif
                    @if($report->subject) <span class="text-muted ms-2"><i class="la la-book"></i> {{ $report->subject->name }}</span> @endif
                </div>
                <div class="col-md-4">
                    {{-- Class picker --}}
                    <form method="GET" action="{{ route('admin.grade-reports.transcript', $report->id) }}" class="d-flex gap-2 flex-wrap">
                        <select name="class_id" class="form-control form-control-sm">
                            <option value="">@lang('grades_admin.pick_class')</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="la la-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(!$classId)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="la la-table la-3x text-muted"></i>
                <p class="mt-3 text-muted">{{ trans('grades_admin.transcript_no_class') }}</p>
            </div>
        </div>
    @elseif($columns->isEmpty())
        <div class="alert alert-warning">
            <i class="la la-exclamation-triangle"></i>
            {{ trans('grades_admin.no_columns') }}
            <a href="{{ route('admin.grade-reports.edit', $report->id) }}" class="alert-link">{{ trans('grades_admin.edit_report') }}</a>
        </div>
    @elseif($students->isEmpty())
        <div class="alert alert-secondary">{{ trans('grades_admin.no_students') }}</div>
    @else
        <div class="card" id="transcript-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    {{ $report->title }}
                    @if($classId)
                        — {{ optional($classes->firstWhere('id', $classId))?->name }}
                    @endif
                </h4>
                <small class="text-muted">{{ $students->count() }} @lang('grades_admin.no_students')</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>{{ trans('grades_admin.student_name') }}</th>
                                @foreach($columns as $col)
                                    <th class="text-center" style="min-width:90px;">
                                        {{ $col->title }}
                                        <br><small class="text-muted">{{ rtrim(rtrim(number_format($col->max_score, 2, '.', ''), '0'), '.') }}</small>
                                    </th>
                                @endforeach
                                <th class="text-center" style="width:90px;">{{ trans('grades_admin.transcript_total') }}</th>
                                <th class="text-center" style="width:80px;">{{ trans('grades_admin.transcript_pct') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $i => $student)
                                @php
                                    $rowTotal   = 0;
                                    $maxPossible = 0;
                                    foreach ($columns as $col) {
                                        $val = $values[$student->id . '-' . $col->id] ?? null;
                                        if ($col->is_in_total && $col->max_score > 0 && $col->weight > 0) {
                                            $maxPossible += $col->weight;
                                            if ($val && $val->score !== null) {
                                                $rowTotal += ($val->score / $col->max_score) * $col->weight;
                                            }
                                        } elseif ($val && $val->score !== null) {
                                            $rowTotal += $val->score;
                                        }
                                    }
                                    $pct = $maxPossible > 0 ? round(($rowTotal / $maxPossible) * 100, 1) : null;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="font-weight-bold">{{ $student->name }}</td>
                                    @foreach($columns as $col)
                                        @php $val = $values[$student->id . '-' . $col->id] ?? null; @endphp
                                        <td class="text-center {{ $val ? '' : 'text-muted' }}">
                                            @if($val && $val->score !== null)
                                                {{ rtrim(rtrim(number_format($val->score, 2, '.', ''), '0'), '.') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center font-weight-bold">
                                        {{ $rowTotal > 0 ? round($rowTotal, 2) : '—' }}
                                    </td>
                                    <td class="text-center text-muted">
                                        {{ $pct !== null && $pct > 0 ? $pct . '%' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
    @page { size: A4; margin: 1.5cm 1.5cm 2cm; }
    .content-header, .content-header-right, form, .btn, nav, header, .sidebar, aside,
    .main-menu, .header-navbar, footer.footer, .breadcrumb-wrapper,
    .no-print, .pagination { display: none !important; }
    body::before {
        content: "{{ $brand_name_ar ?? 'المنصة الذهبية' }} — كشف درجات";
        display: block;
        background: {{ $brand_secondary_color ?? '#14233A' }};
        color: {{ $brand_primary_color ?? '#C9A227' }};
        font-size: 14pt; font-weight: bold; text-align: center;
        padding: 8px; margin-bottom: 12px;
    }
    #transcript-card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    .card-body, .table-responsive { overflow: visible !important; }
    table { width: 100% !important; border-collapse: collapse; }
    th, td { border: 1px solid #ccc !important; padding: 4px 6px; font-size: 9pt; }
    th { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    a[href]:after { content: ''; }
}
</style>
@endpush
@endsection
