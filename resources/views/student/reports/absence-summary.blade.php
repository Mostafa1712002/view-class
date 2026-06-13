@extends('layouts.admin')

@section('title', 'ملخص الحضور والغياب')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">ملخص الحضور والغياب</h1>
        <a href="{{ route('student.reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="la la-arrow-right"></i> العودة للتقارير
        </a>
    </div>

    @if($academicYear)
        <p class="text-muted mb-4">العام الدراسي: <strong>{{ $academicYear->name }}</strong></p>
    @endif

    {{-- Stat cards --}}
    <div class="row mb-4">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card bg-dark text-white text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $stats['total'] }}</h2>
                    <small>إجمالي السجلات</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card bg-success text-white text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $stats['present'] }}</h2>
                    <small>حاضر</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card bg-danger text-white text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $stats['absent'] }}</h2>
                    <small>غائب</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card bg-warning text-dark text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $stats['late'] }}</h2>
                    <small>متأخر</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card bg-info text-white text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $stats['excused'] }}</h2>
                    <small>غياب بعذر</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card {{ $stats['attendanceRate'] >= 75 ? 'bg-success' : 'bg-danger' }} text-white text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $stats['attendanceRate'] }}%</h2>
                    <small>نسبة الحضور</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance rate bar --}}
    @if($stats['total'] > 0)
    <div class="card">
        <div class="card-header"><h5 class="mb-0">توزيع الحضور</h5></div>
        <div class="card-body">
            <div class="mb-2 d-flex justify-content-between">
                <span>نسبة الحضور الفعلية</span>
                <strong>{{ $stats['attendanceRate'] }}%</strong>
            </div>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-success" style="width: {{ $stats['total'] > 0 ? round($stats['present'] / $stats['total'] * 100) : 0 }}%"
                     title="حاضر: {{ $stats['present'] }}">حاضر</div>
                <div class="progress-bar bg-warning" style="width: {{ $stats['total'] > 0 ? round($stats['late'] / $stats['total'] * 100) : 0 }}%"
                     title="متأخر: {{ $stats['late'] }}">متأخر</div>
                <div class="progress-bar bg-info" style="width: {{ $stats['total'] > 0 ? round($stats['excused'] / $stats['total'] * 100) : 0 }}%"
                     title="بعذر: {{ $stats['excused'] }}">بعذر</div>
                <div class="progress-bar bg-danger" style="width: {{ $stats['total'] > 0 ? round($stats['absent'] / $stats['total'] * 100) : 0 }}%"
                     title="غائب: {{ $stats['absent'] }}">غائب</div>
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="la la-calendar display-1 text-muted"></i>
            <p class="mt-3 text-muted">لا توجد بيانات حضور للعام الدراسي الحالي</p>
        </div>
    </div>
    @endif
</div>
@endsection
