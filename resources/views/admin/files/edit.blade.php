@extends('layouts.admin')

@section('title', 'تعديل الملف')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.files.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">تعديل الملف</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-4">
                <i class="la {{ $file->icon }} me-2"></i>
                <strong>{{ $file->original_name }}</strong> ({{ $file->formatted_size }})
            </div>

            <form action="{{ route('admin.files.update', $file) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">اسم الملف <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $file->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">نوع الملف <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="material" {{ old('type', $file->type) == 'material' ? 'selected' : '' }}>مادة تعليمية</option>
                            <option value="assignment" {{ old('type', $file->type) == 'assignment' ? 'selected' : '' }}>واجب</option>
                            <option value="resource" {{ old('type', $file->type) == 'resource' ? 'selected' : '' }}>مرجع</option>
                            <option value="other" {{ old('type', $file->type) == 'other' ? 'selected' : '' }}>أخرى</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">المادة</label>
                        <select name="subject_id" class="form-select">
                            <option value="">-- اختر المادة --</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ old('subject_id', $file->subject_id) == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">الصف</label>
                        <select name="class_id" class="form-select">
                            <option value="">-- اختر الصف --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id', $file->class_id) == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }} - {{ $class->section->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">العام الدراسي</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">-- اختر العام --</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ old('academic_year_id', $file->academic_year_id) == $year->id ? 'selected' : '' }}>
                                    {{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $file->description) }}</textarea>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_public" value="1" class="form-check-input" id="is_public" {{ old('is_public', $file->is_public) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_public">ملف عام (متاح لجميع الطلاب)</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save me-1"></i>حفظ التغييرات
                    </button>
                    <a href="{{ route('admin.files.index') }}" class="btn btn-secondary">@lang('common.cancel')</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
