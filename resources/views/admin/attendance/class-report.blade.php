@extends('layouts.admin')

@section('title', 'تقرير حضور الصف')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تقرير حضور الصف</h1>
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil-square me-1"></i>
            تسجيل الحضور
        </a>
    </div>

    {{-- Selection Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">الصف <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select" required>
                        <option value="">اختر الصف</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
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
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>
                        عرض التقرير
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($report)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $report['class']->name }}</h5>
                    <small class="text-muted">
                        {{ $report['academic_year']->name }} |
                        من {{ \Carbon\Carbon::parse($report['start_date'])->format('Y-m-d') }}
                        إلى {{ \Carbon\Carbon::parse($report['end_date'])->format('Y-m-d') }}
                    </small>
                </div>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    طباعة
                </button>
            </div>
            <div class="card-body">
                @if(count($report['student_stats']) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الطالب</th>
                                    <th class="text-center">إجمالي</th>
                                    <th class="text-center text-success">حاضر</th>
                                    <th class="text-center text-danger">غائب</th>
                                    <th class="text-center text-warning">متأخر</th>
                                    <th class="text-center text-info">بعذر</th>
                                    <th class="text-center">نسبة الحضور</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['student_stats'] as $index => $stats)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('admin.attendance.student-report', ['student_id' => $stats['student']->id, 'academic_year_id' => request('academic_year_id')]) }}">
                                                {{ $stats['student']->name }}
                                            </a>
                                        </td>
                                        <td class="text-center">{{ $stats['total'] }}</td>
                                        <td class="text-center text-success">{{ $stats['present'] }}</td>
                                        <td class="text-center text-danger">{{ $stats['absent'] }}</td>
                                        <td class="text-center text-warning">{{ $stats['late'] }}</td>
                                        <td class="text-center text-info">{{ $stats['excused'] }}</td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar {{ $stats['attendance_rate'] >= 80 ? 'bg-success' : ($stats['attendance_rate'] >= 60 ? 'bg-warning' : 'bg-danger') }}"
                                                     style="width: {{ $stats['attendance_rate'] }}%">
                                                    {{ $stats['attendance_rate'] }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary --}}
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>إحصائيات الصف</h6>
                                    @php
                                        $avgRate = collect($report['student_stats'])->avg('attendance_rate');
                                        $totalAbsent = collect($report['student_stats'])->sum('absent');
                                        $lowAttendance = collect($report['student_stats'])->where('attendance_rate', '<', 80)->count();
                                    @endphp
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td>عدد الطلاب:</td>
                                            <td class="text-end">{{ count($report['student_stats']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>متوسط نسبة الحضور:</td>
                                            <td class="text-end">{{ number_format($avgRate, 1) }}%</td>
                                        </tr>
                                        <tr>
                                            <td>إجمالي أيام الغياب:</td>
                                            <td class="text-end text-danger">{{ $totalAbsent }}</td>
                                        </tr>
                                        <tr>
                                            <td>طلاب بحضور أقل من 80%:</td>
                                            <td class="text-end text-warning">{{ $lowAttendance }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                        <p class="mt-3 text-muted">لا توجد سجلات حضور</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
