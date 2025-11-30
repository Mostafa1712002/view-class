@extends('layouts.admin')

@section('title', $assignment->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">{{ $assignment->title }}</h1>
            <small class="text-muted">{{ $assignment->subject?->name }} - {{ $assignment->classRoom?->name }}</small>
        </div>
        <div>
            <a href="{{ route('admin.assignments.edit', $assignment) }}" class="btn btn-warning">
                <i class="la la-edit me-1"></i>تعديل
            </a>
        </div>
    </div>

    @php $stats = $assignment->submission_stats; @endphp

    {{-- إحصائيات --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    <small>إجمالي الطلاب</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $stats['submitted'] }}</h3>
                    <small>تم التسليم</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $stats['graded'] }}</h3>
                    <small>تم التقييم</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $stats['pending'] }}</h3>
                    <small>لم يسلم</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- تفاصيل الواجب --}}
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">تفاصيل الواجب</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">المعلم:</td>
                            <td>{{ $assignment->teacher?->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">الدرجة القصوى:</td>
                            <td>{{ $assignment->max_score }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">تاريخ التسليم:</td>
                            <td class="{{ $assignment->is_overdue ? 'text-danger' : '' }}">
                                {{ $assignment->due_date->format('Y-m-d') }}
                                @if($assignment->due_time)
                                    {{ $assignment->due_time->format('H:i') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">التأخير:</td>
                            <td>
                                @if($assignment->allow_late_submission)
                                    <span class="badge bg-success">مسموح</span>
                                    @if($assignment->late_penalty_percent > 0)
                                        <small class="text-muted">(خصم {{ $assignment->late_penalty_percent }}%)</small>
                                    @endif
                                @else
                                    <span class="badge bg-danger">غير مسموح</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">الحالة:</td>
                            <td><span class="badge bg-{{ $assignment->status_color }}">{{ $assignment->status_label }}</span></td>
                        </tr>
                    </table>

                    @if($assignment->description)
                        <hr>
                        <h6>الوصف:</h6>
                        <p>{{ $assignment->description }}</p>
                    @endif

                    @if($assignment->instructions)
                        <h6>التعليمات:</h6>
                        <p>{{ $assignment->instructions }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- قائمة الطلاب --}}
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">تسليمات الطلاب</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th class="text-center">الحالة</th>
                                    <th class="text-center">الدرجة</th>
                                    <th class="text-center">تقييم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    @php $submission = $submissions->get($student->id); @endphp
                                    <tr>
                                        <td>{{ $student->name }}</td>
                                        <td class="text-center">
                                            @if($submission)
                                                <span class="badge bg-{{ $submission->status_color }}">
                                                    {{ $submission->status_label }}
                                                </span>
                                                @if($submission->is_late)
                                                    <span class="badge bg-warning text-dark">متأخر</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">لم يسلم</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($submission && $submission->score !== null)
                                                <span class="badge bg-{{ $submission->score_percentage >= 50 ? 'success' : 'danger' }} fs-6">
                                                    {{ $submission->score }}/{{ $assignment->max_score }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#gradeModal{{ $student->id }}">
                                                <i class="la la-edit"></i>
                                            </button>

                                            {{-- Modal للتقييم --}}
                                            <div class="modal fade" id="gradeModal{{ $student->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('admin.assignments.grade', [$assignment, $student]) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">تقييم {{ $student->name }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <div class="mb-3">
                                                                    <label class="form-label">الدرجة (من {{ $assignment->max_score }})</label>
                                                                    <input type="number" name="score" class="form-control" value="{{ $submission?->score }}" min="0" max="{{ $assignment->max_score }}" step="0.5" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">ملاحظات</label>
                                                                    <textarea name="feedback" class="form-control" rows="3">{{ $submission?->feedback }}</textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                                <button type="submit" class="btn btn-primary">حفظ التقييم</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
