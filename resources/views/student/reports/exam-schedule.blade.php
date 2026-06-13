@extends('layouts.admin')

@section('title', 'جدول الاختبارات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">جدول الاختبارات</h1>
    </div>

    @if($academicYear)
        <p class="text-muted mb-4">العام الدراسي: <strong>{{ $academicYear->name }}</strong></p>
    @endif

    <div class="card">
        <div class="card-header"><h5 class="mb-0">الاختبارات المقررة</h5></div>
        <div class="card-body">
            @if($exams->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الاختبار</th>
                            <th>المادة</th>
                            <th>النوع</th>
                            <th>بداية الاختبار</th>
                            <th>نهاية الاختبار</th>
                            <th>المجموع الكلي</th>
                            <th class="text-center">الحالة</th>
                            <th>درجتي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exams as $exam)
                        @php $result = $resultsByExam->get($exam->id); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $exam->title }}</td>
                            <td>{{ $exam->subject?->name ?? '-' }}</td>
                            <td>{{ \App\Models\Exam::TYPES[$exam->type] ?? $exam->type }}</td>
                            <td>{{ $exam->start_time?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $exam->end_time?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $exam->total_marks }}</td>
                            <td class="text-center">
                                @php
                                    $statusBg = match($exam->status) {
                                        'scheduled' => 'secondary',
                                        'active'    => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'dark',
                                        default     => 'light',
                                    };
                                    $statusLabel = \App\Models\Exam::STATUSES[$exam->status] ?? $exam->status;
                                @endphp
                                <span class="badge bg-{{ $statusBg }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                @if($result)
                                    <span class="fw-bold {{ $result->score >= ($exam->pass_marks ?? 0) ? 'text-success' : 'text-danger' }}">
                                        {{ $result->score }}
                                    </span>
                                    / {{ $exam->total_marks }}
                                    @if($result->percentage !== null)
                                        <span class="text-muted ms-1">({{ round($result->percentage, 1) }}%)</span>
                                    @endif
                                @elseif($exam->status === 'active')
                                    <a href="{{ route('student.exams.show', $exam->id) }}" class="btn btn-sm btn-primary">دخول الاختبار</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="la la-clipboard-list display-1 text-muted"></i>
                <p class="mt-3 text-muted">لا توجد اختبارات مقررة للعام الدراسي الحالي</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
