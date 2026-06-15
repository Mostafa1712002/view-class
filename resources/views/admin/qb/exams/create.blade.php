@extends('layouts.app')

@section('title', 'إنشاء اختبار')
@section('body_class', 'theme-light')

@php
    $deliveryLabel = ['electronic' => 'إلكتروني', 'paper' => 'ورقي'];
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">إنشاء اختبار {{ $deliveryLabel[$delivery] ?? '' }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.exams.index') }}">قائمة الاختبارات</a></li>
                <li class="breadcrumb-item active">إنشاء</li>
            </ol>
        </div>
    </div>
</div>

@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ route('admin.qb.exams.store') }}">
    @csrf
    <input type="hidden" name="delivery_type" value="{{ $delivery }}">

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3">المعلومات الأساسية</h6>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">عنوان الاختبار <span class="text-danger">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">نوع الاختبار</label>
                <input type="text" class="form-control" value="{{ $deliveryLabel[$delivery] ?? $delivery }}" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label">المادة</label>
                <select name="subject_id" class="form-select"><option value="">—</option>
                    @foreach ($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id')==$s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">الفصل الدراسي</label>
                <select name="semester_id" class="form-select"><option value="">—</option>
                    @foreach ($semesters as $sem)<option value="{{ $sem->id }}" @selected(old('semester_id')==$sem->id)>{{ $sem->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">درجة النجاح</label>
                <input type="number" step="0.5" name="pass_score" value="{{ old('pass_score') }}" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">الوصف</label>
                <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
            </div>
        </div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3">المدارس والصفوف والجدولة</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">الصفوف المستهدفة (grade level)</label>
                <div class="border rounded p-2" style="max-height:160px;overflow:auto">
                    @php
                        $allSchools = collect($tree['compounds'])->flatMap(fn($g)=>$g['schools'])->merge($tree['ungrouped']);
                        $grades = $allSchools->isNotEmpty() ? range(1, 12) : range(1, 12);
                    @endphp
                    @foreach (range(1, 12) as $gl)
                        <label class="d-inline-flex align-items-center me-3 mb-1" style="font-size:13px">
                            <input type="checkbox" name="grade_levels[]" value="{{ $gl }}" class="form-check-input me-1"> الصف {{ $gl }}
                        </label>
                    @endforeach
                </div>
                <small class="text-muted">اختيار الصفوف اختياري (يدعم تعدد الصفوف).</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">تاريخ البداية</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">تاريخ النهاية</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">مدة الاختبار (دقيقة)</label>
                <input type="number" name="duration_minutes" value="{{ old('duration_minutes') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">طريقة اختيار الأسئلة</label>
                <select name="selection_strategy" class="form-select">
                    <option value="manual">يدويًا من بنك الأسئلة</option>
                    <option value="random">عشوائيًا حسب الفلاتر</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">عدد الأسئلة (للعشوائي)</label>
                <input type="number" name="questions_target" value="{{ old('questions_target') }}" class="form-control">
            </div>
        </div>
    </div></div>

    @if ($delivery === 'electronic')
    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3">إعدادات الاختبار الإلكتروني</h6>
        <div class="row g-2">
            <div class="col-md-4"><label class="form-check"><input type="checkbox" name="allow_direct_access" value="1" class="form-check-input" checked> السماح بالدخول المباشر للطالب</label></div>
            <div class="col-md-4"><label class="form-check"><input type="checkbox" name="show_result_immediately" value="1" class="form-check-input"> إظهار النتيجة مباشرة</label></div>
            <div class="col-md-4"><label class="form-check"><input type="checkbox" name="allow_retake" value="1" class="form-check-input"> السماح بإعادة المحاولة</label></div>
            <div class="col-md-4"><label class="form-check"><input type="checkbox" name="shuffle_questions" value="1" class="form-check-input"> ترتيب الأسئلة عشوائي</label></div>
            <div class="col-md-4"><label class="form-check"><input type="checkbox" name="shuffle_answers" value="1" class="form-check-input"> ترتيب الإجابات عشوائي</label></div>
        </div>
    </div></div>
    @endif

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning"><x-svg-icon name="check-circle-fill" :size="15" /> حفظ ومتابعة لاختيار الأسئلة</button>
        <a href="{{ route('admin.qb.exams.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>
@endsection
