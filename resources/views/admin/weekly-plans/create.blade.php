@extends('layouts.app')

@section('title', 'إضافة خطة أسبوعية')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">إضافة خطة أسبوعية</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.weekly-plans.index') }}">الخطط الأسبوعية</a></li>
                        <li class="breadcrumb-item active">إضافة</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header"><h4 class="card-title">بيانات الخطة</h4></div>
        <div class="card-body">
            <form action="{{ route('manage.weekly-plans.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-1">
                        <label class="form-label">المعلم <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-control @error('teacher_id') is-invalid @enderror" required>
                            @if($teachers->count() == 1)
                                <option value="{{ $teachers->first()->id }}" selected>{{ $teachers->first()->name }}</option>
                            @else
                                <option value="">اختر المعلم</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('teacher_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 mb-1">
                        <label class="form-label">المادة <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-control @error('subject_id') is-invalid @enderror" required>
                            <option value="">اختر المادة</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 mb-1">
                        <label class="form-label">الفصل <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control @error('class_id') is-invalid @enderror" required>
                            <option value="">اختر الفصل</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }} - {{ $class->division }} ({{ $class->section->name ?? '' }})
                                </option>
                            @endforeach
                        </select>
                        @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 mb-1">
                        <label class="form-label">بداية الأسبوع <span class="text-danger">*</span></label>
                        <select name="week_start_date" class="form-control @error('week_start_date') is-invalid @enderror" required>
                            <option value="{{ $currentWeekStart->format('Y-m-d') }}" {{ old('week_start_date') == $currentWeekStart->format('Y-m-d') ? 'selected' : '' }}>
                                الأسبوع الحالي ({{ $currentWeekStart->format('Y-m-d') }})
                            </option>
                            <option value="{{ $nextWeekStart->format('Y-m-d') }}" {{ old('week_start_date') == $nextWeekStart->format('Y-m-d') ? 'selected' : '' }}>
                                الأسبوع القادم ({{ $nextWeekStart->format('Y-m-d') }})
                            </option>
                        </select>
                        @error('week_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr>
                <h5>محتوى الخطة</h5>

                <div class="row">
                    <div class="col-md-6 mb-1">
                        <label class="form-label">أهداف الأسبوع</label>
                        <textarea name="objectives" class="form-control" rows="3">{{ old('objectives') }}</textarea>
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label">المواضيع</label>
                        <textarea name="topics" class="form-control" rows="3">{{ old('topics') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-1">
                        <label class="form-label">الأنشطة</label>
                        <textarea name="activities" class="form-control" rows="3">{{ old('activities') }}</textarea>
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label">الموارد والوسائل</label>
                        <textarea name="resources" class="form-control" rows="3">{{ old('resources') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-1">
                        <label class="form-label">التقييم</label>
                        <textarea name="assessment" class="form-control" rows="3">{{ old('assessment') }}</textarea>
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label">الواجبات</label>
                        <textarea name="homework" class="form-control" rows="3">{{ old('homework') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-1">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-2">
                    <button type="submit" class="btn btn-primary">حفظ الخطة</button>
                    <a href="{{ route('manage.weekly-plans.index') }}" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
