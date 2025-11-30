@extends('layouts.admin')

@section('title', 'لوحة التحكم')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">مرحباً {{ $student->name }}</h1>
            @if($class)
                <small class="text-muted">{{ $class->name }} - {{ $academicYear?->name }}</small>
            @endif
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle display-4"></i>
                    <h2 class="mb-0 mt-2">{{ $attendanceStats['present'] }}</h2>
                    <small>أيام الحضور</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="bi bi-x-circle display-4"></i>
                    <h2 class="mb-0 mt-2">{{ $attendanceStats['absent'] }}</h2>
                    <small>أيام الغياب</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <i class="bi bi-clock display-4"></i>
                    <h2 class="mb-0 mt-2">{{ $attendanceStats['late'] }}</h2>
                    <small>أيام التأخير</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-award display-4"></i>
                    <h2 class="mb-0 mt-2">{{ $gradeAverage ? number_format($gradeAverage, 1) : '-' }}%</h2>
                    <small>المعدل العام</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Upcoming Exams --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">الاختبارات القادمة</h5>
                    <a href="{{ route('student.exams') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
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

        {{-- Recent Grades --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">آخر الدرجات</h5>
                    <a href="{{ route('student.grades') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    @forelse($recentGrades as $grade)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">{{ $grade->subject->name ?? '-' }}</h6>
                                <small class="text-muted">{{ $grade->term }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $grade->total >= 50 ? 'success' : 'danger' }} fs-6">
                                    {{ number_format($grade->total, 1) }}%
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-award display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد درجات منشورة</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Attendance --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">سجل الحضور الأخير</h5>
                    <a href="{{ route('student.attendance') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    @forelse($recentAttendance as $attendance)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $attendance->date->format('Y-m-d') }}</span>
                            <span class="badge bg-{{ $attendance->status_color }}">
                                {{ $attendance->status_label }}
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-calendar display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد سجلات حضور</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Exam Results --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">نتائج الاختبارات</h5>
                    <a href="{{ route('student.exams') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    @forelse($recentExamResults as $result)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">{{ $result->exam->title ?? '-' }}</h6>
                                <small class="text-muted">{{ $result->exam->subject->name ?? '-' }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ ($result->score / $result->exam->total_marks * 100) >= 50 ? 'success' : 'danger' }} fs-6">
                                    {{ $result->score }}/{{ $result->exam->total_marks }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-file-text display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد نتائج اختبارات</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
