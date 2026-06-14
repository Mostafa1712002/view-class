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

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Attendance Details --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">سجل الحضور التفصيلي</h5>
            </div>
            <div class="card-body">
                @if($attendances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>اليوم</th>
                                    <th>النوع</th>
                                    <th>المادة</th>
                                    <th>الحصة</th>
                                    <th>المعلم</th>
                                    <th class="text-center">الحالة</th>
                                    <th class="text-center">حالة العذر</th>
                                    <th>ملاحظات</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendances as $attendance)
                                    <tr>
                                        <td class="text-nowrap">{{ $attendance->date->format('Y-m-d') }}</td>
                                        <td>{{ $attendance->date->translatedFormat('l') }}</td>
                                        <td>
                                            @if($attendance->period)
                                                <span class="badge bg-info text-dark">حصة</span>
                                            @else
                                                <span class="badge bg-secondary">يومي</span>
                                            @endif
                                        </td>
                                        <td>{{ $attendance->subject?->name ?? '—' }}</td>
                                        <td>
                                            @if($attendance->period)
                                                الحصة {{ $attendance->period }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $attendance->teacher?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $attendance->status_color }}">
                                                {{ $attendance->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($attendance->excuse_status === 'accepted')
                                                <span class="badge bg-success">مقبول</span>
                                            @elseif($attendance->excuse_status === 'rejected')
                                                <span class="badge bg-danger">مرفوض</span>
                                            @elseif($attendance->excuse_status === 'pending')
                                                <span class="badge bg-warning text-dark">قيد المراجعة</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ $attendance->notes ?? '—' }}</span>
                                        </td>
                                        <td>
                                            @if(in_array($attendance->status, ['absent', 'late']) && $attendance->excuse_status === null)
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#excuse-modal-{{ $attendance->id }}">
                                                    <i class="bi bi-pencil-square me-1"></i>تقديم عذر
                                                </button>
                                            @endif
                                        </td>
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

{{-- Excuse Submission Modals --}}
@if($stats)
    @foreach($attendances as $attendance)
        @if(in_array($attendance->status, ['absent', 'late']) && $attendance->excuse_status === null)
        <div class="modal fade" id="excuse-modal-{{ $attendance->id }}" tabindex="-1"
             aria-labelledby="excuse-label-{{ $attendance->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="excuse-label-{{ $attendance->id }}">
                            تقديم عذر —
                            {{ $attendance->date->format('Y-m-d') }}
                            ({{ $attendance->status_label }})
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST"
                          action="{{ route('parent.attendance.excuse', [$child->id, $attendance->id]) }}">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    نص العذر <span class="text-danger">*</span>
                                </label>
                                <textarea name="excuse_text" rows="4" class="form-control" required
                                          minlength="5" maxlength="1000"
                                          placeholder="اكتب سبب الغياب أو التأخر..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i>إرسال العذر
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endforeach
@endif

@endsection
