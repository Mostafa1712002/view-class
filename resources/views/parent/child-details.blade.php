@extends('layouts.admin')

@section('title', $child->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('parent.dashboard') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">{{ $child->name }}</h1>
            <small class="text-muted">{{ $class?->name ?? 'غير مسجل' }} - {{ $academicYear?->name }}</small>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $attendanceStats['total'] }}</h3>
                    <small>إجمالي الأيام</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $attendanceStats['present'] }}</h3>
                    <small>حاضر</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $attendanceStats['absent'] }}</h3>
                    <small>غائب</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $attendanceStats['late'] }}</h3>
                    <small>متأخر</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $attendanceStats['excused'] }}</h3>
                    <small>بعذر</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $attendanceStats['rate'] }}%</h3>
                    <small>نسبة الحضور</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Grades by Subject --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">الدرجات حسب المادة</h5>
                    <a href="{{ route('parent.child.grades', $child) }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    @forelse($grades as $subjectData)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-0">{{ $subjectData['subject']->name }}</h6>
                            </div>
                            <span class="badge bg-{{ $subjectData['average'] >= 50 ? 'success' : 'danger' }} fs-6">
                                {{ $subjectData['average'] }}%
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-award display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد درجات</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Upcoming Exams --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">الاختبارات القادمة</h5>
                </div>
                <div class="card-body">
                    @forelse($upcomingExams as $exam)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">{{ $exam->title }}</h6>
                                <small class="text-muted">{{ $exam->subject->name ?? '-' }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-info">{{ $exam->exam_date->format('Y-m-d') }}</span>
                                <small class="d-block text-muted">{{ $exam->duration }} دقيقة</small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-check display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد اختبارات قادمة</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Attendance --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">سجل الحضور الأخير</h5>
                    <a href="{{ route('parent.child.attendance', $child) }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    @if($recentAttendance->count() > 0)
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
                                    @foreach($recentAttendance as $attendance)
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
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-calendar display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد سجلات حضور</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
