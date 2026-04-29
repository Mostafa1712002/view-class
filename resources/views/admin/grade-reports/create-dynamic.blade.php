@extends('layouts.app')

@section('title', 'إنشاء تقرير درجات — ديناميكي')

@section('content')
@php($isRtl = app()->getLocale() === 'ar')
<div class="content-header">
    <h2 class="content-header-title">إنشاء تقرير درجات — ديناميكي</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.index') }}">إدارة الدرجات</a></li>
            <li class="breadcrumb-item active">إنشاء تقرير</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-secondary">
        <strong>الخطوة 1 من 4:</strong> اختيار النوع — <span class="badge badge-primary">ديناميكي</span>
        &nbsp;•&nbsp; (الثابت + كشف الدرجات قيد التطوير لهذا الإصدار)
    </div>

    <form method="POST" action="{{ route('admin.grade-reports.store') }}">
        @csrf
        <div class="card">
            <div class="card-header"><h4 class="card-title">الخطوة 2: إعداد التقرير</h4></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">عنوان التقرير <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">العام الدراسي</label>
                        <select name="academic_year_id" class="form-control">
                            <option value="">— اختر —</option>
                            @foreach($years as $y)
                                <option value="{{ $y->id }}" @selected(old('academic_year_id') == $y->id)>{{ $y->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الفصل الدراسي</label>
                        <select name="academic_term_id" class="form-control">
                            <option value="">— اختر —</option>
                            @foreach($terms as $t)
                                <option value="{{ $t->id }}" @selected(old('academic_term_id') == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">الصف الدراسي</label>
                        <select name="class_id" class="form-control">
                            <option value="">— اختر —</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" @selected(old('class_id') == $c->id)>{{ $c->name }} (الصف {{ $c->grade_level }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">تاريخ بدء إدخال الدرجات</label>
                        <input type="date" name="grade_input_starts_at" class="form-control" value="{{ old('grade_input_starts_at') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">تاريخ انتهاء إدخال الدرجات</label>
                        <input type="date" name="grade_input_ends_at" class="form-control" value="{{ old('grade_input_ends_at') }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">تاريخ بدء الاحتساب</label>
                        <input type="date" name="calc_starts_at" class="form-control" value="{{ old('calc_starts_at') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">تاريخ نهاية الاحتساب</label>
                        <input type="date" name="calc_ends_at" class="form-control" value="{{ old('calc_ends_at') }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">فتح التقرير للطالب</label>
                        <input type="date" name="opens_at" class="form-control" value="{{ old('opens_at') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">إغلاق التقرير</label>
                        <input type="date" name="closes_at" class="form-control" value="{{ old('closes_at') }}">
                    </div>

                    <div class="col-12">
                        <hr>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="include_behavior" value="1" id="include_behavior" @checked(old('include_behavior'))>
                            <label class="form-check-label" for="include_behavior">تضمين درجة سلوكيات الطالب</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="show_subject_bilingual" value="1" id="show_subject_bilingual" @checked(old('show_subject_bilingual'))>
                            <label class="form-check-label" for="show_subject_bilingual">عرض أسماء المواد باللغتين</label>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <strong class="d-block mb-2">إعدادات عرض التقرير:</strong>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="visible_to_student" value="1" id="visible_to_student" @checked(old('visible_to_student', true))>
                            <label class="form-check-label" for="visible_to_student">للطالب</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="visible_to_parent" value="1" id="visible_to_parent" @checked(old('visible_to_parent', true))>
                            <label class="form-check-label" for="visible_to_parent">لولي الأمر</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="visible_to_teacher" value="1" id="visible_to_teacher" @checked(old('visible_to_teacher', true))>
                            <label class="form-check-label" for="visible_to_teacher">للمعلم</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted">سيتم إنشاء أعمدة افتراضية (الواجبات / اختبارات قصيرة / منتصف / نهائي) — يمكنك تعديلها بعد الحفظ.</small>
                <button type="submit" class="btn btn-primary float-{{ $isRtl ? 'left' : 'right' }}">
                    <i class="la la-save"></i> حفظ وإنشاء
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
