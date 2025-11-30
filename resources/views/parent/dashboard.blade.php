@extends('layouts.admin')

@section('title', 'لوحة التحكم')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">مرحباً {{ $parent->name }}</h1>
            <small class="text-muted">{{ $academicYear?->name }}</small>
        </div>
    </div>

    @if($childrenData->count() > 0)
        <div class="row">
            @foreach($childrenData as $childData)
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">{{ $childData['student']->name }}</h5>
                                <small>{{ $childData['class']?->name ?? 'غير مسجل في صف' }}</small>
                            </div>
                            <a href="{{ route('parent.child', $childData['student']) }}" class="btn btn-light btn-sm">
                                التفاصيل
                            </a>
                        </div>
                        <div class="card-body">
                            {{-- Quick Stats --}}
                            <div class="row mb-3">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <h4 class="mb-0 {{ $childData['attendance_rate'] >= 80 ? 'text-success' : 'text-warning' }}">
                                            {{ $childData['attendance_rate'] }}%
                                        </h4>
                                        <small class="text-muted">نسبة الحضور</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <h4 class="mb-0 {{ $childData['grade_average'] >= 50 ? 'text-success' : 'text-danger' }}">
                                            {{ $childData['grade_average'] ?? '-' }}%
                                        </h4>
                                        <small class="text-muted">المعدل العام</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Recent Attendance --}}
                            <h6 class="border-bottom pb-2">آخر سجلات الحضور</h6>
                            <div class="mb-3">
                                @forelse($childData['recent_attendance']->take(3) as $attendance)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small>{{ $attendance->date->format('m/d') }}</small>
                                        <span class="badge bg-{{ $attendance->status_color }}">{{ $attendance->status_label }}</span>
                                    </div>
                                @empty
                                    <small class="text-muted">لا توجد سجلات</small>
                                @endforelse
                            </div>

                            {{-- Recent Grades --}}
                            <h6 class="border-bottom pb-2">آخر الدرجات</h6>
                            <div>
                                @forelse($childData['recent_grades'] as $grade)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small>{{ $grade->subject->name ?? '-' }}</small>
                                        <span class="badge bg-{{ $grade->total >= 50 ? 'success' : 'danger' }}">
                                            {{ number_format($grade->total, 1) }}%
                                        </span>
                                    </div>
                                @empty
                                    <small class="text-muted">لا توجد درجات</small>
                                @endforelse
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-flex gap-2">
                                <a href="{{ route('parent.child.grades', $childData['student']) }}" class="btn btn-outline-primary btn-sm flex-fill">
                                    <i class="bi bi-award me-1"></i>الدرجات
                                </a>
                                <a href="{{ route('parent.child.attendance', $childData['student']) }}" class="btn btn-outline-info btn-sm flex-fill">
                                    <i class="bi bi-calendar-check me-1"></i>الحضور
                                </a>
                                <a href="{{ route('parent.child.schedule', $childData['student']) }}" class="btn btn-outline-secondary btn-sm flex-fill">
                                    <i class="bi bi-calendar3 me-1"></i>الجدول
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <h4 class="mt-3">لا يوجد أبناء مسجلين</h4>
                <p class="text-muted">يرجى التواصل مع إدارة المدرسة لربط حسابك بأبنائك</p>
            </div>
        </div>
    @endif
</div>
@endsection
