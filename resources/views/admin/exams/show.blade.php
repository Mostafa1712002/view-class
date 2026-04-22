@extends('layouts.admin')

@section('title', $exam->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $exam->title }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}">الاختبارات</a></li>
                    <li class="breadcrumb-item active">{{ $exam->title }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.exams.questions.index', $exam) }}" class="btn btn-info">
                <i class="bi bi-list-ol me-1"></i>
                إدارة الأسئلة
            </a>
            <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i>
                تعديل
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Main Info --}}
        <div class="col-md-8">
            {{-- Exam Details Card --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تفاصيل الاختبار</h5>
                    <div>
                        <span class="badge {{ $exam->status_class }} fs-6">{{ $exam->status_label }}</span>
                        @if($exam->is_published)
                            <span class="badge bg-success ms-1">منشور</span>
                        @else
                            <span class="badge bg-secondary ms-1">غير منشور</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($exam->description)
                        <p class="text-muted">{{ $exam->description }}</p>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">المادة:</th>
                                    <td>{{ $exam->subject->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>الصف:</th>
                                    <td>{{ $exam->classRoom->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>العام الدراسي:</th>
                                    <td>{{ $exam->academicYear->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>المعلم:</th>
                                    <td>{{ $exam->teacher->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>النوع:</th>
                                    <td><span class="badge bg-info">{{ $exam->type_label }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">الدرجة الكلية:</th>
                                    <td>{{ number_format($exam->total_marks, 1) }}</td>
                                </tr>
                                <tr>
                                    <th>درجة النجاح:</th>
                                    <td>{{ $exam->pass_marks ? number_format($exam->pass_marks, 1) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>المدة:</th>
                                    <td>{{ $exam->duration_minutes ? $exam->duration_minutes . ' دقيقة' : 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <th>عدد المحاولات:</th>
                                    <td>{{ $exam->attempts_allowed }}</td>
                                </tr>
                                <tr>
                                    <th>عدد الأسئلة:</th>
                                    <td>{{ $exam->questions->count() }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($exam->start_time || $exam->end_time)
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>وقت البدء:</strong>
                                {{ $exam->start_time ? $exam->start_time->format('Y-m-d H:i') : '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>وقت الانتهاء:</strong>
                                {{ $exam->end_time ? $exam->end_time->format('Y-m-d H:i') : '-' }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Questions Preview --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">الأسئلة ({{ $exam->questions->count() }})</h5>
                    <a href="{{ route('admin.exams.questions.create', $exam) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>
                        إضافة سؤال
                    </a>
                </div>
                <div class="card-body">
                    @forelse($exam->questions as $index => $question)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge bg-secondary">السؤال {{ $index + 1 }}</span>
                                <span class="badge bg-info">{{ $question->type_label }}</span>
                            </div>
                            <p class="mb-2">{{ $question->question }}</p>

                            @if($question->type === 'multiple_choice' && $question->options)
                                <ul class="list-unstyled mb-2">
                                    @foreach($question->options as $option)
                                        <li class="{{ $question->correct_answer === $option ? 'text-success fw-bold' : '' }}">
                                            @if($question->correct_answer === $option)
                                                <i class="bi bi-check-circle-fill me-1"></i>
                                            @else
                                                <i class="bi bi-circle me-1"></i>
                                            @endif
                                            {{ $option }}
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif($question->type === 'true_false')
                                <p class="text-success mb-2">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    الإجابة: {{ $question->correct_answer === 'true' ? 'صح' : 'خطأ' }}
                                </p>
                            @endif

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">الدرجة: {{ number_format($question->marks, 1) }}</span>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.exams.questions.edit', [$exam, $question]) }}" class="btn btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-question-circle display-4 text-muted"></i>
                            <p class="mt-2 text-muted">لا توجد أسئلة بعد</p>
                            <a href="{{ route('admin.exams.questions.create', $exam) }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-1"></i>
                                إضافة أول سؤال
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Student Results --}}
            @if($exam->studentExams->count() > 0)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">نتائج الطلاب</h5>
                        <a href="{{ route('admin.exams.results', $exam) }}" class="btn btn-sm btn-outline-primary">
                            عرض التفاصيل
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>@lang('common.student')</th>
                                        <th>@lang('common.status')</th>
                                        <th>@lang('common.grade')</th>
                                        <th>النسبة</th>
                                        <th>التقدير</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($exam->studentExams->take(10) as $studentExam)
                                        <tr>
                                            <td>{{ $studentExam->student->name ?? '-' }}</td>
                                            <td><span class="badge {{ $studentExam->status_class }}">{{ $studentExam->status_label }}</span></td>
                                            <td>{{ $studentExam->score !== null ? number_format($studentExam->score, 1) : '-' }}</td>
                                            <td>{{ $studentExam->percentage !== null ? number_format($studentExam->percentage, 1) . '%' : '-' }}</td>
                                            <td>{{ $studentExam->getGradeLabel() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-md-4">
            {{-- Actions --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">الإجراءات</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if(!$exam->is_published)
                            <form action="{{ route('admin.exams.publish', $exam) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100" {{ $exam->questions->count() === 0 ? 'disabled' : '' }}>
                                    <i class="bi bi-send me-1"></i>
                                    نشر الاختبار
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.exams.unpublish', $exam) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary w-100">
                                    <i class="bi bi-eye-slash me-1"></i>
                                    إلغاء النشر
                                </button>
                            </form>
                        @endif

                        @if($exam->status === 'scheduled')
                            <form action="{{ route('admin.exams.activate', $exam) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-play-fill me-1"></i>
                                    تفعيل الاختبار
                                </button>
                            </form>
                        @elseif($exam->status === 'active')
                            <form action="{{ route('admin.exams.complete', $exam) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-stop-fill me-1"></i>
                                    إنهاء الاختبار
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.exams.results', $exam) }}" class="btn btn-info">
                            <i class="bi bi-bar-chart me-1"></i>
                            عرض النتائج
                        </a>
                    </div>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">إحصائيات</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h3 class="mb-0">{{ $statistics['total_students'] }}</h3>
                            <small class="text-muted">إجمالي الطلاب</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h3 class="mb-0 text-success">{{ $statistics['completed'] }}</h3>
                            <small class="text-muted">مكتمل</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h3 class="mb-0 text-warning">{{ $statistics['in_progress'] }}</h3>
                            <small class="text-muted">قيد التقدم</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h3 class="mb-0 text-secondary">{{ $statistics['not_started'] }}</h3>
                            <small class="text-muted">لم يبدأ</small>
                        </div>
                    </div>

                    @if($statistics['completed'] > 0)
                        <hr>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th>المتوسط:</th>
                                <td class="text-end">{{ number_format($statistics['average_score'], 1) }}%</td>
                            </tr>
                            <tr>
                                <th>أعلى درجة:</th>
                                <td class="text-end text-success">{{ number_format($statistics['highest_score'], 1) }}%</td>
                            </tr>
                            <tr>
                                <th>أقل درجة:</th>
                                <td class="text-end text-danger">{{ number_format($statistics['lowest_score'], 1) }}%</td>
                            </tr>
                            @if($statistics['pass_rate'] !== null)
                                <tr>
                                    <th>نسبة النجاح:</th>
                                    <td class="text-end">{{ number_format($statistics['pass_rate'], 1) }}%</td>
                                </tr>
                            @endif
                        </table>
                    @endif
                </div>
            </div>

            {{-- Options Summary --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">الخيارات</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            @if($exam->shuffle_questions)
                                <i class="bi bi-check-circle text-success me-1"></i>
                            @else
                                <i class="bi bi-x-circle text-danger me-1"></i>
                            @endif
                            خلط الأسئلة
                        </li>
                        <li class="mb-2">
                            @if($exam->shuffle_answers)
                                <i class="bi bi-check-circle text-success me-1"></i>
                            @else
                                <i class="bi bi-x-circle text-danger me-1"></i>
                            @endif
                            خلط الإجابات
                        </li>
                        <li>
                            @if($exam->show_results)
                                <i class="bi bi-check-circle text-success me-1"></i>
                            @else
                                <i class="bi bi-x-circle text-danger me-1"></i>
                            @endif
                            إظهار النتائج للطالب
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
