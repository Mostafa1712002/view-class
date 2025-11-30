@extends('layouts.app')

@section('title', 'تعديل الخطة الأسبوعية')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">تعديل الخطة الأسبوعية</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.weekly-plans.index') }}">الخطط الأسبوعية</a></li>
                        <li class="breadcrumb-item active">تعديل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">الخطة: {{ $weeklyPlan->teacher->name }} - {{ $weeklyPlan->subject->name }} ({{ $weeklyPlan->week_label }})</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('manage.weekly-plans.update', $weeklyPlan) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-1">
                        <label class="form-label">المادة <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-control @error('subject_id') is-invalid @enderror" required>
                            <option value="">اختر المادة</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ old('subject_id', $weeklyPlan->subject_id) == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label">الفصل <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control @error('class_id') is-invalid @enderror" required>
                            <option value="">اختر الفصل</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id', $weeklyPlan->class_id) == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }} - {{ $class->division }} ({{ $class->section->name ?? '' }})
                                </option>
                            @endforeach
                        </select>
                        @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr>
                <h5>محتوى الخطة</h5>

                <div class="row">
                    <div class="col-md-6 mb-1">
                        <label class="form-label">أهداف الأسبوع</label>
                        <textarea name="objectives" class="form-control" rows="4">{{ old('objectives', $weeklyPlan->objectives) }}</textarea>
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label">المواضيع</label>
                        <textarea name="topics" class="form-control" rows="4">{{ old('topics', $weeklyPlan->topics) }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-1">
                        <label class="form-label">الأنشطة</label>
                        <textarea name="activities" class="form-control" rows="4">{{ old('activities', $weeklyPlan->activities) }}</textarea>
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label">الموارد والوسائل</label>
                        <textarea name="resources" class="form-control" rows="4">{{ old('resources', $weeklyPlan->resources) }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-1">
                        <label class="form-label">التقييم</label>
                        <textarea name="assessment" class="form-control" rows="4">{{ old('assessment', $weeklyPlan->assessment) }}</textarea>
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label">الواجبات</label>
                        <textarea name="homework" class="form-control" rows="4">{{ old('homework', $weeklyPlan->homework) }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-1">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $weeklyPlan->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-2">
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    <a href="{{ route('manage.weekly-plans.show', $weeklyPlan) }}" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
