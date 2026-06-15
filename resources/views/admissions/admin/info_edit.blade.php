@extends('layouts.app')
@section('title','تعديل قسم معلومات التسجيل')
@section('body_class','theme-light')

@section('content')
<div class="content-header">
    <h2>تعديل قسم: {{ $section->title }}</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admissions.info.index') }}">معلومات التسجيل</a></li>
        <li class="breadcrumb-item active">تعديل</li>
    </ol>
</div>

<div class="content-body">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admissions.info.update', $section->id) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="col-md-8 mb-2">
                    <label>العنوان</label>
                    <input type="text" name="title" value="{{ old('title', $section->title) }}" class="form-control" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label>الترتيب</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $section->sort_order) }}" class="form-control">
                </div>
                <div class="col-md-2 mb-2">
                    <label>الحالة</label>
                    <select name="is_active" class="form-control">
                        <option value="1" {{ $section->is_active ? 'selected' : '' }}>إظهار</option>
                        <option value="0" {{ !$section->is_active ? 'selected' : '' }}>إخفاء</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label>المحتوى (يدعم النص والروابط والجداول — RTL)</label>
                <textarea name="content" class="form-control" rows="10" dir="rtl">{{ old('content', $section->content) }}</textarea>
                <small class="text-muted">يمكن إدخال HTML بسيط (روابط، جداول، فقرات).</small>
            </div>
            <button class="btn btn-primary"><x-svg-icon name="check2" :size="16" /> حفظ</button>
            <a href="{{ route('admissions.info.index') }}" class="btn btn-outline-secondary">عودة</a>
        </form>
    </div></div>
</div>
@endsection
