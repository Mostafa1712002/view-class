@extends('layouts.admin')

@section('title', 'إدارة أسئلة الاختبار')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">أسئلة الاختبار: {{ $exam->title }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}">الاختبارات</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.show', $exam) }}">{{ $exam->title }}</a></li>
                    <li class="breadcrumb-item active">الأسئلة</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.exams.questions.create', $exam) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                إضافة سؤال
            </a>
            <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-right me-1"></i>
                العودة للاختبار
            </a>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ $questions->count() }}</h3>
                    <small>عدد الأسئلة</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ number_format($totalMarks, 1) }}</h3>
                    <small>مجموع الدرجات</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h3 class="mb-0">{{ number_format($exam->total_marks, 1) }}</h3>
                    <small>الدرجة الكلية للاختبار</small>
                </div>
            </div>
        </div>
    </div>

    @if($totalMarks != $exam->total_marks)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>تنبيه:</strong> مجموع درجات الأسئلة ({{ number_format($totalMarks, 1) }}) لا يتطابق مع الدرجة الكلية للاختبار ({{ number_format($exam->total_marks, 1) }})
        </div>
    @endif

    {{-- Questions List --}}
    <div class="card">
        <div class="card-body">
            @if($questions->count() > 0)
                <div id="questions-list">
                    @foreach($questions as $question)
                        <div class="border rounded p-3 mb-3 question-item" data-id="{{ $question->id }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark">{{ $loop->iteration }}</span>
                                    <span class="badge bg-info">{{ $question->type_label }}</span>
                                    <span class="badge bg-secondary">{{ number_format($question->marks, 1) }} درجة</span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <form action="{{ route('admin.exams.questions.duplicate', [$exam, $question]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary" title="نسخ">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.exams.questions.edit', [$exam, $question]) }}" class="btn btn-outline-warning" title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.exams.questions.destroy', [$exam, $question]) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السؤال؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <p class="mb-2 fw-bold">{{ $question->question }}</p>

                            @if($question->type === 'multiple_choice' && $question->options)
                                <div class="row">
                                    @foreach($question->options as $index => $option)
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-1">
                                                @if($question->correct_answer === $option)
                                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                @else
                                                    <i class="bi bi-circle text-muted me-2"></i>
                                                @endif
                                                <span class="{{ $question->correct_answer === $option ? 'text-success fw-bold' : '' }}">
                                                    {{ chr(65 + $index) }}. {{ $option }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($question->type === 'true_false')
                                <div class="d-flex gap-4">
                                    <span class="{{ $question->correct_answer === 'true' ? 'text-success fw-bold' : 'text-muted' }}">
                                        @if($question->correct_answer === 'true')
                                            <i class="bi bi-check-circle-fill me-1"></i>
                                        @else
                                            <i class="bi bi-circle me-1"></i>
                                        @endif
                                        صح
                                    </span>
                                    <span class="{{ $question->correct_answer === 'false' ? 'text-success fw-bold' : 'text-muted' }}">
                                        @if($question->correct_answer === 'false')
                                            <i class="bi bi-check-circle-fill me-1"></i>
                                        @else
                                            <i class="bi bi-circle me-1"></i>
                                        @endif
                                        خطأ
                                    </span>
                                </div>
                            @elseif($question->type === 'short_answer' && $question->correct_answer)
                                <p class="text-success mb-0">
                                    <i class="bi bi-check-circle me-1"></i>
                                    الإجابة النموذجية: {{ $question->correct_answer }}
                                </p>
                            @endif

                            @if($question->explanation)
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-muted">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        التوضيح: {{ $question->explanation }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-question-circle display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد أسئلة بعد</p>
                    <a href="{{ route('admin.exams.questions.create', $exam) }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>
                        إضافة أول سؤال
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
