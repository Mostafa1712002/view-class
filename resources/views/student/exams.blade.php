@extends('layouts.admin')

@section('title', 'الاختبارات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">الاختبارات</h1>
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

    <div class="row">
        {{-- Upcoming Exams --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>الاختبارات القادمة</h5>
                </div>
                <div class="card-body">
                    @forelse($upcomingExams as $exam)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">{{ $exam->title }}</h6>
                                    <small class="text-muted">{{ $exam->subject->name ?? '-' }}</small>
                                </div>
                                <span class="badge bg-info">{{ $exam->exam_date->format('Y-m-d') }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="bi bi-clock me-1"></i>{{ $exam->duration }} دقيقة</span>
                                <span><i class="bi bi-award me-1"></i>{{ $exam->total_marks }} درجة</span>
                                @if($exam->start_time)
                                    <span><i class="bi bi-play-circle me-1"></i>{{ $exam->start_time }}</span>
                                @endif
                            </div>
                            @if($exam->description)
                                <p class="mt-2 mb-0 small text-muted">{{ $exam->description }}</p>
                            @endif
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

        {{-- Completed Exams with Results --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>نتائج الاختبارات</h5>
                </div>
                <div class="card-body">
                    @forelse($completedExams as $result)
                        @php
                            $percentage = $result->exam->total_marks > 0
                                ? ($result->score / $result->exam->total_marks) * 100
                                : 0;
                        @endphp
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">{{ $result->exam->title }}</h6>
                                    <small class="text-muted">{{ $result->exam->subject->name ?? '-' }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{{ $percentage >= 50 ? 'success' : 'danger' }} fs-6">
                                        {{ $result->score }}/{{ $result->exam->total_marks }}
                                    </span>
                                    <small class="d-block text-muted">{{ number_format($percentage, 1) }}%</small>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-{{ $percentage >= 50 ? 'success' : 'danger' }}"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                            <small class="text-muted">{{ $result->exam->exam_date->format('Y-m-d') }}</small>
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
