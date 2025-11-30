@extends('layouts.admin')

@section('title', 'تقرير حضور الطالب')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تقرير حضور الطالب</h1>
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil-square me-1"></i>
            تسجيل الحضور
        </a>
    </div>

    {{-- Selection Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الطالب <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">اختر الطالب</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">العام الدراسي <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="form-select" required>
                        <option value="">اختر العام</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>
                        عرض
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($report)
        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $report['stats']['total'] }}</h3>
                        <small>إجمالي الأيام</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $report['stats']['present'] }}</h3>
                        <small>حاضر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $report['stats']['absent'] }}</h3>
                        <small>غائب</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $report['stats']['late'] }}</h3>
                        <small>متأخر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $report['stats']['excused'] }}</h3>
                        <small>غياب بعذر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-dark text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $report['stats']['attendance_rate'] }}%</h3>
                        <small>نسبة الحضور</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Monthly Stats --}}
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">إحصائيات شهرية</h5>
                    </div>
                    <div class="card-body">
                        @if($report['monthly_stats']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>الشهر</th>
                                            <th class="text-center">حاضر</th>
                                            <th class="text-center">غائب</th>
                                            <th class="text-center">متأخر</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['monthly_stats'] as $month => $stats)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }}</td>
                                                <td class="text-center text-success">{{ $stats['present'] }}</td>
                                                <td class="text-center text-danger">{{ $stats['absent'] }}</td>
                                                <td class="text-center text-warning">{{ $stats['late'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center">لا توجد بيانات</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Attendance Details --}}
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">سجل الحضور: {{ $report['student']->name }}</h5>
                            <small class="text-muted">{{ $report['academic_year']->name }}</small>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>
                            طباعة
                        </button>
                    </div>
                    <div class="card-body">
                        @if($report['attendances']->count() > 0)
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>اليوم</th>
                                            <th class="text-center">الحالة</th>
                                            <th>الصف</th>
                                            <th>المادة</th>
                                            <th>ملاحظات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['attendances'] as $attendance)
                                            <tr>
                                                <td>{{ $attendance->date->format('Y-m-d') }}</td>
                                                <td>{{ $attendance->date->translatedFormat('l') }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $attendance->status_color }}">
                                                        {{ $attendance->status_label }}
                                                    </span>
                                                </td>
                                                <td>{{ $attendance->classRoom->name ?? '-' }}</td>
                                                <td>{{ $attendance->subject->name ?? '-' }}</td>
                                                <td>{{ $attendance->notes ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <p class="mt-3 text-muted">لا توجد سجلات حضور</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
