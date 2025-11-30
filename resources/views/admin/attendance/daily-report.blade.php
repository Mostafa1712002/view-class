@extends('layouts.admin')

@section('title', 'التقرير اليومي للحضور')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">التقرير اليومي للحضور</h1>
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil-square me-1"></i>
            تسجيل الحضور
        </a>
    </div>

    {{-- Selection Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
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
                <div class="col-md-4">
                    <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}" required>
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
        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0">{{ $report['stats']['present'] }}</h2>
                        <small>حاضر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0">{{ $report['stats']['absent'] }}</h2>
                        <small>غائب</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h2 class="mb-0">{{ $report['stats']['late'] }}</h2>
                        <small>متأخر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0">{{ $report['stats']['excused'] }}</h2>
                        <small>غياب بعذر</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $report['class']->name }}</h5>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($report['date'])->format('Y-m-d') }}</small>
                </div>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    طباعة
                </button>
            </div>
            <div class="card-body">
                @if($report['stats']['total'] > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الطالب</th>
                                    <th class="text-center">الحالة</th>
                                    <th>وقت الوصول</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['class']->students as $index => $student)
                                    @php
                                        $studentAttendance = $report['attendances'][$student->id] ?? collect();
                                        $attendance = $studentAttendance->first();
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student->name }}</td>
                                        <td class="text-center">
                                            @if($attendance)
                                                <span class="badge bg-{{ $attendance->status_color }}">
                                                    {{ $attendance->status_label }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">غير مسجل</span>
                                            @endif
                                        </td>
                                        <td>{{ $attendance?->arrival_time?->format('H:i') ?? '-' }}</td>
                                        <td>{{ $attendance?->notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                        <p class="mt-3 text-muted">لم يتم تسجيل الحضور لهذا اليوم</p>
                        <a href="{{ route('admin.attendance.index', ['class_id' => request('class_id'), 'date' => $date]) }}" class="btn btn-primary">
                            <i class="bi bi-pencil-square me-1"></i>
                            تسجيل الحضور
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
