@extends('layouts.admin')

@section('title', 'تعديل الواجب')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">تعديل الواجب</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.assignments.update', $assignment) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">عنوان الواجب <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $assignment->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">المادة <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                            <option value="">-- اختر المادة --</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ old('subject_id', $assignment->subject_id) == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">الصف <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                            <option value="">-- اختر الصف --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id', $assignment->class_id) == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }} - {{ $class->section->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $assignment->description) }}</textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">التعليمات</label>
                        <textarea name="instructions" class="form-control" rows="3">{{ old('instructions', $assignment->instructions) }}</textarea>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">الدرجة القصوى <span class="text-danger">*</span></label>
                        <input type="number" name="max_score" class="form-control @error('max_score') is-invalid @enderror" value="{{ old('max_score', $assignment->max_score) }}" min="1" max="1000" required>
                        @error('max_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">تاريخ التسليم <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $assignment->due_date->format('Y-m-d')) }}" required>
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">وقت التسليم</label>
                        <input type="time" name="due_time" class="form-control" value="{{ old('due_time', $assignment->due_time?->format('H:i')) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="allow_late_submission" value="1" class="form-check-input" id="allow_late" {{ old('allow_late_submission', $assignment->allow_late_submission) ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_late">السماح بالتسليم المتأخر</label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">نسبة خصم التأخير (%)</label>
                        <input type="number" name="late_penalty_percent" class="form-control" value="{{ old('late_penalty_percent', $assignment->late_penalty_percent) }}" min="0" max="100">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="draft" {{ old('status', $assignment->status) == 'draft' ? 'selected' : '' }}>مسودة</option>
                            <option value="published" {{ old('status', $assignment->status) == 'published' ? 'selected' : '' }}>منشور</option>
                            <option value="closed" {{ old('status', $assignment->status) == 'closed' ? 'selected' : '' }}>مغلق</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save me-1"></i>حفظ التغييرات
                    </button>
                    <a href="{{ route('admin.assignments.index') }}" class="btn btn-secondary">@lang('common.cancel')</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
