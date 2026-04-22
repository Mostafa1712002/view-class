@extends('layouts.admin')

@section('title', 'تسجيل الحضور')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تسجيل الحضور</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.attendance.daily-report') }}" class="btn btn-outline-info">
                <i class="bi bi-calendar-day me-1"></i>
                التقرير اليومي
            </a>
            <a href="{{ route('admin.attendance.student-report') }}" class="btn btn-outline-info">
                <i class="bi bi-person-lines-fill me-1"></i>
                تقرير الطالب
            </a>
        </div>
    </div>

    {{-- Selection Form --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">اختر الصف والتاريخ</h5>
        </div>
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
                    <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">الحصة</label>
                    <select name="period" class="form-select">
                        <option value="">اليوم كامل</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ request('period') == $i ? 'selected' : '' }}>
                                الحصة {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">المادة</label>
                    <select name="subject_id" class="form-select">
                        <option value="">بدون مادة</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">العام الدراسي</label>
                    <select name="academic_year_id" class="form-select">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id || $year->is_current ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Attendance Entry --}}
    @if($attendances->count() > 0)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">حضور {{ $selectedClass->name }}</h5>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($selectedDate)->format('Y-m-d') }} {{ $selectedPeriod ? '- الحصة ' . $selectedPeriod : '' }}</small>
                </div>
                <form action="{{ route('admin.attendance.mark-all-present') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="period" value="{{ request('period') }}">
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') }}">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-all me-1"></i>
                        تسجيل الكل حاضر
                    </button>
                </form>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.attendance.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="period" value="{{ request('period') }}">
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') ?? $academicYears->firstWhere('is_current', true)?->id }}">

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الطالب</th>
                                    <th class="text-center">@lang('common.status')</th>
                                    <th>وقت الوصول</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendances as $index => $attendance)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ $attendance->student->name }}
                                            <input type="hidden" name="attendances[{{ $index }}][student_id]" value="{{ $attendance->student->id }}">
                                        </td>
                                        <td>
                                            <div class="btn-group w-100" role="group">
                                                @foreach(\App\Models\Attendance::STATUSES as $key => $label)
                                                    <input type="radio" class="btn-check" name="attendances[{{ $index }}][status]" id="status_{{ $index }}_{{ $key }}" value="{{ $key }}" {{ $attendance->status === $key ? 'checked' : '' }} autocomplete="off">
                                                    <label class="btn btn-outline-{{ \App\Models\Attendance::STATUS_COLORS[$key] }} btn-sm" for="status_{{ $index }}_{{ $key }}">
                                                        {{ $label }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td style="width: 120px;">
                                            <input type="time" name="attendances[{{ $index }}][arrival_time]" class="form-control form-control-sm" value="{{ $attendance->arrival_time?->format('H:i') }}">
                                        </td>
                                        <td>
                                            <input type="text" name="attendances[{{ $index }}][notes]" class="form-control form-control-sm" value="{{ $attendance->notes }}" placeholder="ملاحظة">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>
                            حفظ الحضور
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @elseif(request('class_id'))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <p class="mt-3 text-muted">لا يوجد طلاب في هذا الصف</p>
            </div>
        </div>
    @endif
</div>
@endsection
