@extends('layouts.admin')

@section('title', 'حضور ' . $child->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('parent.child', $child) }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">سجل حضور {{ $child->name }}</h1>
        </div>
    </div>

    {{-- Year Selection --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">العام الدراسي</label>
                    <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $selectedYear?->id == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($stats)
        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        <small>إجمالي الأيام</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $stats['present'] }}</h3>
                        <small>حاضر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $stats['absent'] }}</h3>
                        <small>غائب</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $stats['late'] }}</h3>
                        <small>متأخر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $stats['excused'] }}</h3>
                        <small>بعذر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-dark text-white">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ $stats['attendance_rate'] }}%</h3>
                        <small>نسبة الحضور</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance Details --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">سجل الحضور التفصيلي</h5>
            </div>
            <div class="card-body">
                @if($attendances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>اليوم</th>
                                    <th class="text-center">@lang('common.status')</th>
                                    <th>المادة</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->date->format('Y-m-d') }}</td>
                                        <td>{{ $attendance->date->translatedFormat('l') }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $attendance->status_color }}">
                                                {{ $attendance->status_label }}
                                            </span>
                                        </td>
                                        <td>{{ $attendance->subject->name ?? '-' }}</td>
                                        <td>{{ $attendance->notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $attendances->links() }}
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-calendar display-4 text-muted"></i>
                        <p class="text-muted mt-2">لا توجد سجلات حضور</p>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar display-1 text-muted"></i>
                <p class="mt-3 text-muted">اختر العام الدراسي لعرض سجل الحضور</p>
            </div>
        </div>
    @endif
</div>
@endsection
