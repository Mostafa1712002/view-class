@extends('layouts.app')

@section('title', 'تعديل الجدول')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">تعديل جدول {{ $schedule->classRoom->name }} - {{ $schedule->classRoom->division }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.schedules.index') }}">الجداول الدراسية</a></li>
                        <li class="breadcrumb-item active">تعديل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.schedules.show', $schedule) }}" class="btn btn-info"><i data-feather="eye"></i> عرض</a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h4 class="card-title">معلومات الجدول</h4>
            <form action="{{ route('manage.schedules.update', $schedule) }}" method="POST" class="d-inline">
                @csrf
                @method('PUT')
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ $schedule->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label" for="is_active">نشط</label>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>الفصل:</strong> {{ $schedule->classRoom->name }} - {{ $schedule->classRoom->division }}</div>
                <div class="col-md-3"><strong>المرحلة:</strong> {{ $schedule->classRoom->section->name ?? '-' }}</div>
                <div class="col-md-3"><strong>السنة الدراسية:</strong> {{ $schedule->academicYear->name }}</div>
                <div class="col-md-3"><strong>الفصل الدراسي:</strong> {{ $schedule->semester_label }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">الجدول الأسبوعي - اضغط على أي خلية للتعديل</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">الحصة</th>
                            @foreach($days as $dayNum => $dayName)
                                @if($dayNum != 5)
                                <th>{{ $dayName }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for($period = 1; $period <= $periodsCount; $period++)
                        <tr>
                            <td class="table-light"><strong>{{ $period }}</strong></td>
                            @foreach($days as $dayNum => $dayName)
                                @if($dayNum != 5)
                                <td class="period-cell" data-day="{{ $dayNum }}" data-period="{{ $period }}" style="cursor: pointer; min-height: 60px;">
                                    @if($timetable[$dayNum][$period])
                                        @php $p = $timetable[$dayNum][$period]; @endphp
                                        <div class="period-content" data-id="{{ $p->id }}" data-subject="{{ $p->subject_id }}" data-teacher="{{ $p->teacher_id }}" data-room="{{ $p->room }}">
                                            <div class="text-primary fw-bold">{{ $p->subject->name }}</div>
                                            <small class="text-muted">{{ $p->teacher->name }}</small>
                                            @if($p->room)
                                                <br><small class="badge bg-light-secondary">{{ $p->room }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted add-hint"><i data-feather="plus-circle"></i></span>
                                    @endif
                                </td>
                                @endif
                            @endforeach
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="periodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة/تعديل حصة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="periodForm" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="day_of_week" id="day_of_week">
                    <input type="hidden" name="period_number" id="period_number">
                    <input type="hidden" id="period_id">

                    <div class="mb-1">
                        <label class="form-label">المادة <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-control" required>
                            <option value="">اختر المادة</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">المعلم <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="teacher_id" class="form-control" required>
                            <option value="">اختر المعلم</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">القاعة</label>
                        <input type="text" name="room" id="room" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="deletePeriodBtn" style="display: none;">حذف</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('common.cancel')</button>
                    <button type="submit" class="btn btn-primary">@lang('common.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('periodModal'));
    const form = document.getElementById('periodForm');
    const deleteBtn = document.getElementById('deletePeriodBtn');
    const scheduleId = {{ $schedule->id }};

    document.querySelectorAll('.period-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            const day = this.dataset.day;
            const period = this.dataset.period;
            const content = this.querySelector('.period-content');

            document.getElementById('day_of_week').value = day;
            document.getElementById('period_number').value = period;

            if (content) {
                document.getElementById('period_id').value = content.dataset.id;
                document.getElementById('subject_id').value = content.dataset.subject;
                document.getElementById('teacher_id').value = content.dataset.teacher;
                document.getElementById('room').value = content.dataset.room || '';
                deleteBtn.style.display = 'block';
            } else {
                document.getElementById('period_id').value = '';
                document.getElementById('subject_id').value = '';
                document.getElementById('teacher_id').value = '';
                document.getElementById('room').value = '';
                deleteBtn.style.display = 'none';
            }

            modal.show();
        });
    });

    form.action = "{{ route('manage.schedules.store-period', $schedule) }}";

    deleteBtn.addEventListener('click', function() {
        if (!confirm('هل أنت متأكد من حذف هذه الحصة؟')) return;

        const periodId = document.getElementById('period_id').value;
        fetch(`/manage/schedules/{{ $schedule->id }}/periods/${periodId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => res.json()).then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });
});
</script>
@endpush
