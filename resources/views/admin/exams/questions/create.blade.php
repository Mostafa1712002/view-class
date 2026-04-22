@extends('layouts.admin')

@section('title', 'إضافة سؤال')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">إضافة سؤال جديد</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}">الاختبارات</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.show', $exam) }}">{{ $exam->title }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.questions.index', $exam) }}">الأسئلة</a></li>
                    <li class="breadcrumb-item active">إضافة</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.exams.questions.index', $exam) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>
            العودة للأسئلة
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.exams.questions.store', $exam) }}" method="POST" id="questionForm">
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">نص السؤال <span class="text-danger">*</span></label>
                            <textarea name="question" class="form-control @error('question') is-invalid @enderror" rows="3" required>{{ old('question') }}</textarea>
                            @error('question')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">نوع السؤال <span class="text-danger">*</span></label>
                                <select name="type" id="questionType" class="form-select @error('type') is-invalid @enderror" required>
                                    @foreach(\App\Models\ExamQuestion::TYPES as $key => $label)
                                        <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">الدرجة <span class="text-danger">*</span></label>
                                <input type="number" name="marks" class="form-control @error('marks') is-invalid @enderror" value="{{ old('marks', 1) }}" min="0.5" step="0.5" required>
                                @error('marks')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">الترتيب</label>
                                <input type="number" name="order" class="form-control @error('order') is-invalid @enderror" value="{{ old('order', $nextOrder) }}" min="0">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Multiple Choice Options --}}
                        <div id="multipleChoiceSection" class="mb-3">
                            <label class="form-label">الخيارات <span class="text-danger">*</span></label>
                            <div id="optionsContainer">
                                @for($i = 0; $i < 4; $i++)
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">{{ chr(65 + $i) }}</span>
                                        <input type="text" name="options[]" class="form-control" value="{{ old('options.' . $i) }}" placeholder="الخيار {{ $i + 1 }}">
                                        <div class="input-group-text">
                                            <input type="radio" name="correct_option" value="{{ $i }}" {{ old('correct_option') == $i ? 'checked' : '' }}>
                                            <label class="ms-1 mb-0">صحيح</label>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOption()">
                                <i class="bi bi-plus me-1"></i>
                                إضافة خيار
                            </button>
                        </div>

                        {{-- True/False Options --}}
                        <div id="trueFalseSection" class="mb-3" style="display: none;">
                            <label class="form-label">الإجابة الصحيحة <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input type="radio" name="correct_answer_tf" value="true" class="form-check-input" id="tfTrue" {{ old('correct_answer_tf') == 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tfTrue">صح</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="correct_answer_tf" value="false" class="form-check-input" id="tfFalse" {{ old('correct_answer_tf') == 'false' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tfFalse">خطأ</label>
                                </div>
                            </div>
                        </div>

                        {{-- Short Answer --}}
                        <div id="shortAnswerSection" class="mb-3" style="display: none;">
                            <label class="form-label">الإجابة النموذجية</label>
                            <input type="text" name="correct_answer_short" class="form-control" value="{{ old('correct_answer_short') }}" placeholder="الإجابة المتوقعة (اختياري)">
                            <small class="text-muted">يمكن ترك هذا الحقل فارغاً للتصحيح اليدوي</small>
                        </div>

                        {{-- Essay (No correct answer needed) --}}
                        <div id="essaySection" class="mb-3" style="display: none;">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                الأسئلة المقالية تتطلب تصحيحاً يدوياً من المعلم
                            </div>
                        </div>

                        <input type="hidden" name="correct_answer" id="correctAnswerHidden">

                        <div class="mb-3">
                            <label class="form-label">التوضيح (اختياري)</label>
                            <textarea name="explanation" class="form-control @error('explanation') is-invalid @enderror" rows="2" placeholder="شرح للإجابة الصحيحة (يظهر للطالب بعد الاختبار)">{{ old('explanation') }}</textarea>
                            @error('explanation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">معلومات الاختبار</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>{{ $exam->title }}</strong></p>
                                <p class="mb-1 text-muted">{{ $exam->subject->name ?? '-' }}</p>
                                <p class="mb-0 text-muted">{{ $exam->classRoom->name ?? '-' }}</p>
                                <hr>
                                <p class="mb-1">الدرجة الكلية: {{ number_format($exam->total_marks, 1) }}</p>
                                <p class="mb-0">الأسئلة الحالية: {{ $exam->questions()->count() }}</p>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="bi bi-lightbulb me-1"></i>
                            <strong>نصيحة:</strong> اختر نوع السؤال أولاً لعرض الحقول المناسبة
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.exams.questions.index', $exam) }}" class="btn btn-light">@lang('common.cancel')</a>
                    <button type="submit" name="add_another" value="1" class="btn btn-outline-primary">
                        <i class="bi bi-plus-lg me-1"></i>
                        حفظ وإضافة آخر
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        حفظ السؤال
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let optionCount = 4;

    function addOption() {
        if (optionCount >= 8) {
            alert('الحد الأقصى 8 خيارات');
            return;
        }
        const container = document.getElementById('optionsContainer');
        const letter = String.fromCharCode(65 + optionCount);
        const html = `
            <div class="input-group mb-2">
                <span class="input-group-text">${letter}</span>
                <input type="text" name="options[]" class="form-control" placeholder="الخيار ${optionCount + 1}">
                <div class="input-group-text">
                    <input type="radio" name="correct_option" value="${optionCount}">
                    <label class="ms-1 mb-0">صحيح</label>
                </div>
                <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove(); optionCount--;">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        optionCount++;
    }

    function updateQuestionType() {
        const type = document.getElementById('questionType').value;

        document.getElementById('multipleChoiceSection').style.display = type === 'multiple_choice' ? 'block' : 'none';
        document.getElementById('trueFalseSection').style.display = type === 'true_false' ? 'block' : 'none';
        document.getElementById('shortAnswerSection').style.display = type === 'short_answer' ? 'block' : 'none';
        document.getElementById('essaySection').style.display = type === 'essay' ? 'block' : 'none';
    }

    document.getElementById('questionType').addEventListener('change', updateQuestionType);
    updateQuestionType();

    document.getElementById('questionForm').addEventListener('submit', function(e) {
        const type = document.getElementById('questionType').value;
        let correctAnswer = '';

        if (type === 'multiple_choice') {
            const selectedOption = document.querySelector('input[name="correct_option"]:checked');
            if (selectedOption) {
                const optionIndex = parseInt(selectedOption.value);
                const options = document.querySelectorAll('input[name="options[]"]');
                if (options[optionIndex]) {
                    correctAnswer = options[optionIndex].value;
                }
            }
        } else if (type === 'true_false') {
            const selectedTf = document.querySelector('input[name="correct_answer_tf"]:checked');
            if (selectedTf) {
                correctAnswer = selectedTf.value;
            }
        } else if (type === 'short_answer') {
            correctAnswer = document.querySelector('input[name="correct_answer_short"]').value;
        }

        document.getElementById('correctAnswerHidden').value = correctAnswer;
    });
</script>
@endpush
@endsection
