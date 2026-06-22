@extends('layouts.app')

@section('title', 'تقرير الحضور الشهري')
@section('body_class', 'theme-light')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">تقرير الحضور الشهري</h2>
    <div class="breadcrumb-wrapper d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
            <li class="breadcrumb-item active">{{ $class->name }} - {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}</li>
        </ol>
        <div class="d-flex align-items-center" style="gap:.4rem">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <x-svg-icon name="arrow-right" :size="14" class="me-1" /> العودة
            </a>
            <form action="{{ route('admin.reports.attendance-report-pdf') }}" method="GET" class="d-inline">
                <input type="hidden" name="class_id" value="{{ $class->id }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit" class="btn btn-danger btn-sm">
                    <x-svg-icon name="file-earmark-pdf" :size="14" class="me-1" /> تصدير PDF
                </button>
            </form>
        </div>
    </div>
</div>

<div class="content-body">
    {{-- فلتر --}}
    <div class="ds-card card mb-4">
        <div class="ds-card-body card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الصف</label>
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ $class->id == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} - {{ $c->section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الشهر</label>
                    <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    {{-- إحصائيات عامة --}}
    @php
        $totalPresent = $attendanceData->sum('present');
        $totalAbsent = $attendanceData->sum('absent');
        $totalLate = $attendanceData->sum('late');
        $totalExcused = $attendanceData->sum('excused');
        $averageRate = $attendanceData->avg('rate');
        $stats = [
            ['v' => $totalPresent, 'l' => 'إجمالي الحضور', 'icon' => 'check-circle', 'tone' => 'success'],
            ['v' => $totalAbsent, 'l' => 'إجمالي الغياب', 'icon' => 'x-circle', 'tone' => 'danger'],
            ['v' => $totalLate, 'l' => 'إجمالي التأخر', 'icon' => 'clock-history', 'tone' => 'warning'],
            ['v' => $totalExcused, 'l' => 'بعذر', 'icon' => 'shield-check', 'tone' => 'info'],
            ['v' => round($averageRate, 1) . '%', 'l' => 'متوسط نسبة الحضور', 'icon' => 'graph-up', 'tone' => 'navy'],
        ];
    @endphp
    <div class="row mb-4">
        @foreach($stats as $s)
            <div class="col-md col-6 mb-3">
                <div class="ds-card card ds-stat h-100">
                    <div class="ds-card-body card-body d-flex align-items-center" style="gap:.7rem">
                        <span class="ds-badge-{{ $s['tone'] }}" style="width:42px;height:42px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                            <x-svg-icon name="{{ $s['icon'] }}" :size="20" />
                        </span>
                        <div>
                            <div style="font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1">{{ $s['v'] }}</div>
                            <div class="text-muted" style="font-size:.74rem">{{ $s['l'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- جدول الحضور --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title mb-0">تفاصيل حضور الطلاب</h5>
            <span class="ds-badge-navy">{{ $attendanceData->count() }}</span>
        </div>
        @if($attendanceData->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover ds-table-tight mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم الطالب</th>
                            <th class="text-center">@lang('common.present')</th>
                            <th class="text-center">@lang('common.absent')</th>
                            <th class="text-center">متأخر</th>
                            <th class="text-center">بعذر</th>
                            <th class="text-center">الإجمالي</th>
                            <th class="text-center">النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendanceData as $index => $data)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td style="font-weight:600">{{ $data['student']->name }}</td>
                                <td class="text-center">{{ $data['present'] }}</td>
                                <td class="text-center">{{ $data['absent'] }}</td>
                                <td class="text-center">{{ $data['late'] }}</td>
                                <td class="text-center">{{ $data['excused'] }}</td>
                                <td class="text-center">{{ $data['total'] }}</td>
                                <td class="text-center">
                                    <span class="ds-badge-{{ $data['rate'] >= 90 ? 'success' : ($data['rate'] >= 75 ? 'info' : ($data['rate'] >= 60 ? 'warning' : 'danger')) }}">
                                        {{ $data['rate'] }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="calendar-x" :size="30" /></div>
                <div class="ds-empty-title">لا توجد سجلات حضور لهذا الشهر</div>
                <div class="ds-empty-desc">جرّب اختيار صف أو شهر آخر.</div>
            </div>
        @endif
    </div>
</div>
@endsection
