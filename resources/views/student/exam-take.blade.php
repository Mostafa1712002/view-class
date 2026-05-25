@extends('layouts.admin')

@section('title', $exam->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $exam->title }}</h1>
        <a href="{{ route('student.exams') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-right me-1"></i>رجوع
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 text-muted small">
                <span><i class="bi bi-journal-bookmark me-1"></i>{{ $exam->subject->name ?? '-' }}</span>
                @if($exam->duration_minutes)
                    <span><i class="bi bi-clock me-1"></i>{{ $exam->duration_minutes }} دقيقة</span>
                @endif
                <span><i class="bi bi-award me-1"></i>{{ $exam->total_marks }} درجة</span>
                <span><i class="bi bi-list-ol me-1"></i>{{ $exam->questions->count() }} سؤال</span>
            </div>
            @if($exam->description)
                <p class="mt-3 mb-0">{{ $exam->description }}</p>
            @endif
        </div>
    </div>

    @if(! $attempt)
        {{-- Intro: not started yet --}}
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-pencil-square display-4 text-primary"></i>
                <h5 class="mt-3">أنت على وشك بدء الاختبار</h5>
                <p class="text-muted">بمجرد الضغط على "ابدأ الاختبار" يبدأ احتساب الوقت.</p>
                <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-play-fill me-1"></i>ابدأ الاختبار
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- Active attempt: render the questions form --}}
        <form method="POST" action="{{ route('student.exams.submit', $exam) }}" id="exam-form">
            @csrf
            @foreach($exam->questions as $index => $question)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-3">
                                <span class="badge bg-secondary me-2">{{ $index + 1 }}</span>
                                {{ $question->question }}
                            </h6>
                            <span class="text-muted small">{{ $question->marks }} درجة</span>
                        </div>

                        @if($question->type === 'multiple_choice')
                            @foreach($question->getOptionsArray() as $opt)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                           name="answers[{{ $question->id }}]"
                                           id="q{{ $question->id }}_{{ $loop->index }}"
                                           value="{{ $opt }}">
                                    <label class="form-check-label" for="q{{ $question->id }}_{{ $loop->index }}">{{ $opt }}</label>
                                </div>
                            @endforeach
                        @elseif($question->type === 'true_false')
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]"
                                       id="q{{ $question->id }}_true" value="صح">
                                <label class="form-check-label" for="q{{ $question->id }}_true">صح</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]"
                                       id="q{{ $question->id }}_false" value="خطأ">
                                <label class="form-check-label" for="q{{ $question->id }}_false">خطأ</label>
                            </div>
                        @elseif($question->type === 'short_answer')
                            <input type="text" class="form-control" name="answers[{{ $question->id }}]"
                                   placeholder="اكتب إجابتك">
                        @else
                            <textarea class="form-control" rows="4" name="answers[{{ $question->id }}]"
                                      placeholder="اكتب إجابتك"></textarea>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="d-flex justify-content-end mb-5">
                <button type="submit" class="btn btn-success"
                        onclick="return confirm('هل أنت متأكد من تسليم الاختبار؟');">
                    <i class="bi bi-check-circle me-1"></i>تسليم الاختبار
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
