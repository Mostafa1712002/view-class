@extends('layouts.admin')

@section('title', 'تقرير الحضور الشهري')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">تقرير الحضور الشهري</h1>
            <small class="text-muted">{{ $class->name }} - {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}</small>
        </div>
        <form action="{{ route('admin.reports.attendance-report-pdf') }}" method="GET" class="d-inline">
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <button type="submit" class="btn btn-danger">
                <i class="la la-file-pdf me-1"></i>تصدير PDF
            </button>
        </form>
    </div>

    {{-- فلتر --}}
    <div class="card mb-4">
        <div class="card-body">
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
    @endphp
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $totalPresent }}</h3>
                    <small>إجمالي الحضور</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $totalAbsent }}</h3>
                    <small>إجمالي الغياب</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $totalLate }}</h3>
                    <small>إجمالي التأخر</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $totalExcused }}</h3>
                    <small>بعذر</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ round($averageRate, 1) }}%</h3>
                    <small>متوسط نسبة الحضور</small>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول الحضور --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">تفاصيل حضور الطلاب</h5>
        </div>
        <div class="card-body">
            @if($attendanceData->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم الطالب</th>
                                <th class="text-center bg-success text-white">حاضر</th>
                                <th class="text-center bg-danger text-white">غائب</th>
                                <th class="text-center bg-warning">متأخر</th>
                                <th class="text-center bg-info text-white">بعذر</th>
                                <th class="text-center">الإجمالي</th>
                                <th class="text-center">النسبة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendanceData as $index => $data)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $data['student']->name }}</td>
                                    <td class="text-center">{{ $data['present'] }}</td>
                                    <td class="text-center">{{ $data['absent'] }}</td>
                                    <td class="text-center">{{ $data['late'] }}</td>
                                    <td class="text-center">{{ $data['excused'] }}</td>
                                    <td class="text-center">{{ $data['total'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $data['rate'] >= 90 ? 'success' : ($data['rate'] >= 75 ? 'info' : ($data['rate'] >= 60 ? 'warning' : 'danger')) }}">
                                            {{ $data['rate'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="la la-calendar-times display-4 text-muted"></i>
                    <p class="text-muted mt-2">لا توجد سجلات حضور لهذا الشهر</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
