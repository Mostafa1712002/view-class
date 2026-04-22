@extends('layouts.admin')

@section('title', 'تعديل الاختبار')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تعديل الاختبار: {{ $exam->title }}</h1>
        <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>
            العودة للاختبار
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.exams.update', $exam) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    {{-- Basic Info --}}
                    <div class="col-md-8">
                        <h5 class="mb-3">المعلومات الأساسية</h5>

                        <div class="mb-3">
                            <label class="form-label">عنوان الاختبار <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $exam->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $exam->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">المادة <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                                    <option value="">اختر المادة</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('subject_id', $exam->subject_id) == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">الصف <span class="text-danger">*</span></label>
                                <select name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                                    <option value="">اختر الصف</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id', $exam->class_id) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">العام الدراسي <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required>
                                    <option value="">اختر العام</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id', $exam->academic_year_id) == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نوع الاختبار <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    @foreach(\App\Models\Exam::TYPES as $key => $label)
                                        <option value="{{ $key }}" {{ old('type', $exam->type) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">الدرجة الكلية <span class="text-danger">*</span></label>
                                <input type="number" name="total_marks" class="form-control @error('total_marks') is-invalid @enderror" value="{{ old('total_marks', $exam->total_marks) }}" min="1" step="0.5" required>
                                @error('total_marks')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">درجة النجاح</label>
                                <input type="number" name="pass_marks" class="form-control @error('pass_marks') is-invalid @enderror" value="{{ old('pass_marks', $exam->pass_marks) }}" min="0" step="0.5">
                                @error('pass_marks')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مدة الاختبار (دقيقة)</label>
                                <input type="number" name="duration_minutes" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="1">
                                @error('duration_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">عدد المحاولات المسموحة</label>
                                <input type="number" name="attempts_allowed" class="form-control @error('attempts_allowed') is-invalid @enderror" value="{{ old('attempts_allowed', $exam->attempts_allowed) }}" min="1" max="10" required>
                                @error('attempts_allowed')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">وقت البدء</label>
                                <input type="datetime-local" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $exam->start_time?->format('Y-m-d\TH:i')) }}">
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">وقت الانتهاء</label>
                                <input type="datetime-local" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', $exam->end_time?->format('Y-m-d\TH:i')) }}">
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Options --}}
                    <div class="col-md-4">
                        <h5 class="mb-3">الخيارات</h5>

                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="shuffle_questions" class="form-check-input" id="shuffle_questions" value="1" {{ old('shuffle_questions', $exam->shuffle_questions) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="shuffle_questions">
                                        خلط الأسئلة
                                    </label>
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" name="shuffle_answers" class="form-check-input" id="shuffle_answers" value="1" {{ old('shuffle_answers', $exam->shuffle_answers) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="shuffle_answers">
                                        خلط الإجابات
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input type="checkbox" name="show_results" class="form-check-input" id="show_results" value="1" {{ old('show_results', $exam->show_results) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_results">
                                        إظهار النتائج للطالب
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Current Status --}}
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">حالة الاختبار</h6>
                            </div>
                            <div class="card-body">
                                <span class="badge {{ $exam->status_class }} fs-6">{{ $exam->status_label }}</span>
                                @if($exam->is_published)
                                    <span class="badge bg-success ms-2">منشور</span>
                                @else
                                    <span class="badge bg-secondary ms-2">غير منشور</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-light">@lang('common.cancel')</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
