@extends('layouts.admin')

@section('title', 'لوحة التحكم')
@section('body_class', 'theme-light')

@push('styles')
<style>
    body.theme-light .student-hero {
        background: linear-gradient(135deg, #fff8e6, #ffffff);
        border: 1px solid #f1e3bd; border-radius: 16px;
        padding: 1.25rem 1.4rem; margin-bottom: 1.5rem;
        display: flex; align-items: center; gap: 1rem;
    }
    body.theme-light .student-hero .avatar {
        width: 56px; height: 56px; border-radius: 16px;
        background: linear-gradient(135deg, #fff6dd, #fde2a8);
        color: var(--gold-500, #cfa046); font-size: 1.6rem; font-weight: 800;
        display: inline-flex; align-items: center; justify-content: center;
        box-shadow: inset 0 0 0 1px rgba(207,160,70,.2);
    }
    body.theme-light .student-hero h1 { font-size: 1.4rem; font-weight: 800; color: #0f172a; }
    body.theme-light .stat-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        padding: 1.1rem 1.2rem; height: 100%;
        display: flex; align-items: center; justify-content: space-between;
        transition: box-shadow .15s ease, transform .15s ease;
    }
    body.theme-light .stat-card:hover { box-shadow: 0 10px 26px rgba(15,23,42,.07); transform: translateY(-2px); }
    body.theme-light .stat-card .value { font-size: 1.9rem; font-weight: 800; line-height: 1; color: #0f172a; }
    body.theme-light .stat-card .label { color: #64748b; font-weight: 600; font-size: .82rem; margin-top: .3rem; }
    body.theme-light .stat-card .ic {
        width: 48px; height: 48px; border-radius: 14px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem;
    }
    body.theme-light .stat-card.present .ic { background: #ecfdf5; color: #047857; }
    body.theme-light .stat-card.absent  .ic { background: #fef2f2; color: #b91c1c; }
    body.theme-light .stat-card.late    .ic { background: #fffbeb; color: #b45309; }
    body.theme-light .stat-card.average .ic { background: #fff6dd; color: var(--gold-500, #cfa046); }
    body.theme-light .student-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; }
    body.theme-light .student-panel .card-header {
        background: #fff; border-bottom: 1px solid #f1f5f9;
        border-radius: 16px 16px 0 0; padding: .9rem 1.1rem;
    }
    body.theme-light .student-panel .card-header h5 { font-weight: 700; color: #0f172a; font-size: 1rem; }
    body.theme-light .student-panel .btn-link-gold {
        color: var(--gold-500, #cfa046); font-weight: 600; font-size: .82rem;
        text-decoration: none; border: 1px solid #f1e3bd; border-radius: 999px; padding: .25rem .8rem;
    }
    body.theme-light .student-panel .btn-link-gold:hover { background: #fff6dd; }
    body.theme-light .soft-badge {
        padding: .22rem .65rem; border-radius: 999px; font-size: .78rem; font-weight: 700;
    }
    body.theme-light .soft-badge.ok   { background: #ecfdf5; color: #047857; }
    body.theme-light .soft-badge.bad  { background: #fef2f2; color: #b91c1c; }
    body.theme-light .soft-badge.info { background: #eff6ff; color: #1d4ed8; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="student-hero">
        <span class="avatar">{{ mb_substr($student->name, 0, 1) }}</span>
        <div>
            <h1 class="mb-0">مرحباً {{ $student->name }}</h1>
            @if($class)
                <small class="text-muted">{{ $class->name }} @if($academicYear?->name) - {{ $academicYear->name }} @endif</small>
            @endif
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card present">
                <div>
                    <div class="value">{{ $attendanceStats['present'] }}</div>
                    <div class="label">أيام الحضور</div>
                </div>
                <span class="ic"><i class="bi bi-check-circle"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card absent">
                <div>
                    <div class="value">{{ $attendanceStats['absent'] }}</div>
                    <div class="label">أيام الغياب</div>
                </div>
                <span class="ic"><i class="bi bi-x-circle"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card late">
                <div>
                    <div class="value">{{ $attendanceStats['late'] }}</div>
                    <div class="label">أيام التأخير</div>
                </div>
                <span class="ic"><i class="bi bi-clock"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card average">
                <div>
                    <div class="value">{{ $gradeAverage ? number_format($gradeAverage, 1).'%' : '—' }}</div>
                    <div class="label">المعدل العام</div>
                </div>
                <span class="ic"><i class="bi bi-award"></i></span>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Upcoming Exams --}}
        <div class="col-md-6 mb-4">
            <div class="student-panel h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">الاختبارات القادمة</h5>
                    <a href="{{ route('student.exams') }}" class="btn-link-gold">عرض الكل</a>
                </div>
                <div class="card-body">
                    @forelse($upcomingExams as $exam)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">{{ $exam->title }}</h6>
                                <small class="text-muted">{{ $exam->subject->name ?? '-' }}</small>
                            </div>
                            <div class="text-end">
                                <span class="soft-badge info">{{ optional($exam->start_time)->format('Y-m-d') ?? '—' }}</span>
                                @if($exam->duration_minutes)
                                    <small class="d-block text-muted">{{ $exam->duration_minutes }} دقيقة</small>
                                @endif
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
            <div class="student-panel h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">آخر الدرجات</h5>
                    <a href="{{ route('student.grades') }}" class="btn-link-gold">عرض الكل</a>
                </div>
                <div class="card-body">
                    @forelse($recentGrades as $grade)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">{{ $grade->subject->name ?? '-' }}</h6>
                                <small class="text-muted">{{ $grade->term }}</small>
                            </div>
                            <div class="text-end">
                                <span class="soft-badge {{ $grade->total >= 50 ? 'ok' : 'bad' }}">
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
            <div class="student-panel h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">سجل الحضور الأخير</h5>
                    <a href="{{ route('student.attendance') }}" class="btn-link-gold">عرض الكل</a>
                </div>
                <div class="card-body">
                    @forelse($recentAttendance as $attendance)
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span>{{ optional($attendance->date)->format('Y-m-d') ?? '—' }}</span>
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
            <div class="student-panel h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">نتائج الاختبارات</h5>
                    <a href="{{ route('student.exams') }}" class="btn-link-gold">عرض الكل</a>
                </div>
                <div class="card-body">
                    @forelse($recentExamResults as $result)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">{{ $result->exam->title ?? '-' }}</h6>
                                <small class="text-muted">{{ $result->exam->subject->name ?? '-' }}</small>
                            </div>
                            <div class="text-end">
                                @php $maxMarks = optional($result->exam)->total_marks ?: 0; @endphp
                                <span class="soft-badge {{ $maxMarks > 0 && ($result->score / $maxMarks * 100) >= 50 ? 'ok' : 'bad' }}">
                                    {{ $result->score }}/{{ $maxMarks ?: '—' }}
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
