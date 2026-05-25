@extends('layouts.admin')

@section('title', 'نتيجة ' . $exam->title)

@section('content')
@php
    $needsManual = $exam->questions->contains(fn ($q) => ! $q->isAutoGradable());
    $percentage = $attempt->percentage ?? 0;
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">نتيجة الاختبار</h1>
        <a href="{{ route('student.exams') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-right me-1"></i>رجوع للاختبارات
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-1">{{ $exam->title }}</h5>
            <small class="text-muted">{{ $exam->subject->name ?? '-' }}</small>

            @if($attempt->status === 'graded')
                <div class="mt-3 d-flex align-items-center gap-3">
                    <span class="badge bg-{{ $percentage >= 50 ? 'success' : 'danger' }} fs-5">
                        {{ rtrim(rtrim(number_format($attempt->score ?? 0, 2), '0'), '.') }}/{{ $exam->total_marks }}
                    </span>
                    <span class="fw-bold">{{ number_format($percentage, 1) }}%</span>
                    <span class="text-muted">{{ $attempt->getGradeLabel() }}</span>
                </div>
            @else
                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-hourglass-split me-1"></i>
                    تم تسليم إجابتك. بعض الأسئلة تحتاج تصحيحاً يدوياً من المعلم وستظهر الدرجة النهائية لاحقاً.
                </div>
            @endif
        </div>
    </div>

    @if($exam->show_results || $attempt->status === 'graded')
        <div class="card">
            <div class="card-header">مراجعة الإجابات</div>
            <div class="card-body">
                @foreach($attempt->answers->sortBy(fn ($a) => $a->question->order) as $index => $ans)
                    @php $q = $ans->question; @endphp
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-2">{{ $loop->iteration }}. {{ $q->question }}</h6>
                            @if($ans->is_correct === true)
                                <span class="badge bg-success">صحيحة</span>
                            @elseif($ans->is_correct === false)
                                <span class="badge bg-danger">خاطئة</span>
                            @else
                                <span class="badge bg-secondary">بانتظار التصحيح</span>
                            @endif
                        </div>
                        <p class="mb-1 small"><span class="text-muted">إجابتك:</span> {{ $ans->answer ?: '—' }}</p>
                        @if($exam->show_results && $q->isAutoGradable() && $q->correct_answer)
                            <p class="mb-0 small text-success"><span class="text-muted">الإجابة الصحيحة:</span> {{ $q->correct_answer }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
