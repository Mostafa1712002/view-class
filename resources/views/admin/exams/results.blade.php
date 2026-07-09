@extends('layouts.admin')

@section('title', 'نتائج الاختبار')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">نتائج الاختبار: {{ $exam->title }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.index') }}">الاختبارات</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.show', $exam) }}">{{ $exam->title }}</a></li>
                    <li class="breadcrumb-item active">النتائج</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route($routePrefix . '.show', $exam) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>
            العودة للاختبار
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $exam->studentExams->count() }}</h2>
                    <small>إجمالي الطلاب</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ $exam->studentExams->where('status', 'graded')->count() }}</h2>
                    <small>تم تصحيحهم</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0">{{ number_format($exam->studentExams->where('status', 'graded')->avg('percentage') ?? 0, 1) }}%</h2>
                    <small>متوسط الدرجات</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    @php
                        $graded = $exam->studentExams->where('status', 'graded');
                        $passRate = $graded->count() > 0 && $exam->pass_marks
                            ? ($graded->where('score', '>=', $exam->pass_marks)->count() / $graded->count()) * 100
                            : 0;
                    @endphp
                    <h2 class="mb-0">{{ number_format($passRate, 1) }}%</h2>
                    <small>نسبة النجاح</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">تفاصيل النتائج</h5>
        </div>
        <div class="card-body">
            @if($exam->studentExams->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الطالب</th>
                                <th>@lang('common.status')</th>
                                <th>بدأ في</th>
                                <th>سلم في</th>
                                <th>@lang('common.grade')</th>
                                <th>النسبة</th>
                                <th>التقدير</th>
                                <th>النتيجة</th>
                                <th>@lang('common.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exam->studentExams->sortByDesc('percentage') as $index => $studentExam)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $studentExam->student->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $studentExam->status_class }}">
                                            {{ $studentExam->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $studentExam->started_at?->format('m/d H:i') ?? '-' }}</td>
                                    <td>{{ $studentExam->submitted_at?->format('m/d H:i') ?? '-' }}</td>
                                    <td>
                                        {{ $studentExam->score !== null ? number_format($studentExam->score, 1) : '-' }}
                                        / {{ number_format($exam->total_marks, 1) }}
                                    </td>
                                    <td>
                                        @if($studentExam->percentage !== null)
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar {{ $studentExam->percentage >= 60 ? 'bg-success' : 'bg-danger' }}"
                                                     style="width: {{ $studentExam->percentage }}%">
                                                    {{ number_format($studentExam->percentage, 1) }}%
                                                </div>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $studentExam->getGradeLabel() }}</td>
                                    <td>
                                        @if($studentExam->status === 'graded')
                                            @if($studentExam->isPassed())
                                                <span class="badge bg-success">ناجح</span>
                                            @else
                                                <span class="badge bg-danger">راسب</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-bs-toggle="modal" data-target="#answersModal{{ $studentExam->id }}" data-bs-target="#answersModal{{ $studentExam->id }}">
                                                <i class="bi bi-eye"></i>
                                                عرض الإجابات
                                            </button>

                                            {{-- === Anti-cheat (ac) — Trello #229 ===
                                                 Re-open control: only for attempts auto-locked by the
                                                 tab-exit limit, and only for users allowed to edit exams. --}}
                                            @if($studentExam->auto_ended && auth()->user()->canDo('exams.edit'))
                                                <form method="POST" action="{{ route($routePrefix . '.attempts.reopen', [$exam, $studentExam]) }}"
                                                      onsubmit="return confirm('سيتم إعادة فتح الاختبار لهذا الطالب وتصفير عدّاد محاولات الخروج. هل تريد المتابعة؟');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-unlock"></i>
                                                        إعادة فتح الاختبار
                                                    </button>
                                                </form>
                                            @elseif($studentExam->auto_ended)
                                                <span class="badge bg-danger align-self-center">
                                                    <i class="bi bi-lock-fill"></i> مُقفل
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Answers Modal --}}
                                <div class="modal fade" id="answersModal{{ $studentExam->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">إجابات {{ $studentExam->student->name ?? 'الطالب' }}</h5>
                                                <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                @foreach($studentExam->answers as $answer)
                                                    <div class="border rounded p-3 mb-3">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span class="badge bg-secondary">السؤال {{ $loop->iteration }}</span>
                                                            <span class="badge {{ $answer->is_correct ? 'bg-success' : ($answer->is_correct === false ? 'bg-danger' : 'bg-warning') }}">
                                                                {{ $answer->marks_obtained ?? 0 }} / {{ $answer->question->marks }}
                                                            </span>
                                                        </div>
                                                        <p class="mb-2"><strong>{{ $answer->question->question }}</strong></p>
                                                        <p class="mb-1">
                                                            <span class="text-muted">إجابة الطالب:</span>
                                                            {{ $answer->answer ?? 'لم يجب' }}
                                                        </p>
                                                        @if($answer->question->correct_answer)
                                                            <p class="mb-0 text-success">
                                                                <span class="text-muted">الإجابة الصحيحة:</span>
                                                                {{ $answer->question->correct_answer }}
                                                            </p>
                                                        @endif
                                                        @if($answer->feedback)
                                                            <hr>
                                                            <p class="mb-0 text-info">
                                                                <i class="bi bi-chat-dots me-1"></i>
                                                                {{ $answer->feedback }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                @endforeach

                                                @if($studentExam->teacher_feedback)
                                                    <div class="alert alert-info">
                                                        <strong>ملاحظات المعلم:</strong>
                                                        {{ $studentExam->teacher_feedback }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">إغلاق</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-x display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد نتائج بعد</p>
                </div>
            @endif
        </div>
    </div>

    {{-- === Anti-cheat (ac) === Exit-attempts log --}}
    @php
        $allExitAttempts = $exam->studentExams->flatMap->exitAttempts;
        $typeLabels = \App\Models\ExamExitAttempt::TYPES;
    @endphp
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-shield-exclamation me-1"></i>سجل محاولات الخروج / الغش</h5>
            <span class="badge bg-danger">{{ $allExitAttempts->count() }} محاولة</span>
        </div>
        <div class="card-body">
            @if($allExitAttempts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الطالب</th>
                                <th>الاختبار</th>
                                <th>وقت المحاولة</th>
                                <th>نوع المحاولة</th>
                                <th>عدد المحاولات</th>
                                <th>إنهاء تلقائي؟</th>
                                <th>الجهاز</th>
                                <th>المتصفح</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $row = 0; @endphp
                            @foreach($exam->studentExams as $studentExam)
                                @foreach($studentExam->exitAttempts as $attempt)
                                    <tr>
                                        <td>{{ ++$row }}</td>
                                        <td>{{ $studentExam->student->name ?? '-' }}</td>
                                        <td>{{ $exam->title }}</td>
                                        <td>{{ $attempt->occurred_at?->format('Y/m/d H:i:s') ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                {{ $typeLabels[$attempt->attempt_type] ?? $attempt->attempt_type }}
                                            </span>
                                        </td>
                                        <td>{{ $attempt->attempt_count }}</td>
                                        <td>
                                            @if($attempt->auto_ended)
                                                <span class="badge bg-danger">نعم</span>
                                            @else
                                                <span class="badge bg-secondary">لا</span>
                                            @endif
                                        </td>
                                        <td>{{ $attempt->device ?? '-' }}</td>
                                        <td>{{ $attempt->browser ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-shield-check display-4 text-success"></i>
                    <p class="mt-3 mb-0 text-muted">لا توجد محاولات خروج مسجلة لهذا الاختبار.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Grade Distribution Chart --}}
    @if($exam->studentExams->where('status', 'graded')->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">توزيع الدرجات</h5>
            </div>
            <div class="card-body">
                @php
                    $graded = $exam->studentExams->where('status', 'graded');
                    $distribution = [
                        'ممتاز (90+)' => $graded->where('percentage', '>=', 90)->count(),
                        'جيد جداً (80-89)' => $graded->whereBetween('percentage', [80, 89.99])->count(),
                        'جيد (70-79)' => $graded->whereBetween('percentage', [70, 79.99])->count(),
                        'مقبول (60-69)' => $graded->whereBetween('percentage', [60, 69.99])->count(),
                        'راسب (<60)' => $graded->where('percentage', '<', 60)->count(),
                    ];
                @endphp
                <div class="row">
                    @foreach($distribution as $label => $count)
                        <div class="col">
                            <div class="text-center">
                                <h3 class="mb-0">{{ $count }}</h3>
                                <small class="text-muted">{{ $label }}</small>
                                <div class="progress mt-2" style="height: 10px;">
                                    <div class="progress-bar" style="width: {{ $graded->count() > 0 ? ($count / $graded->count()) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
