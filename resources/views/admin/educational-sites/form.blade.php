@extends('layouts.app')
@section('body_class','theme-light')
@section('title', $site->exists ? 'تعديل موقع تعليمي' : 'إضافة موقع تعليمي')
@section('content')
@php $isEdit = $site->exists; @endphp
<div class="content-header row">
    <div class="content-header-left col-md-7 mb-2">
        <h2 class="content-header-title mb-0">{{ $isEdit ? 'تعديل موقع تعليمي' : 'إضافة موقع تعليمي' }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.educational-sites.index') }}">المواقع التعليمية</a></li>
            <li class="breadcrumb-item active">{{ $isEdit ? 'تعديل' : 'إضافة' }}</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="ds-card"><div class="ds-card-body">
        <form method="POST"
              action="{{ $isEdit ? route('admin.educational-sites.update', $site->id) : route('admin.educational-sites.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>اسم الموقع بالإنجليزي <span class="text-danger">*</span></label>
                    <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $site->name_en) }}" required dir="ltr">
                </div>
                <div class="form-group col-md-6">
                    <label>اسم الموقع بالعربي</label>
                    <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar', $site->name_ar) }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>الوصف بالعربي</label>
                    <textarea name="description_ar" class="form-control" rows="3">{{ old('description_ar', $site->description_ar) }}</textarea>
                </div>
                <div class="form-group col-md-6">
                    <label>الوصف بالإنجليزي</label>
                    <textarea name="description_en" class="form-control" rows="3" dir="ltr">{{ old('description_en', $site->description_en) }}</textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-8">
                    <label>الرابط <span class="text-danger">*</span></label>
                    <input type="url" name="url" class="form-control" value="{{ old('url', $site->url) }}" placeholder="https://example.com" required dir="ltr">
                </div>
                <div class="form-group col-md-4">
                    <label>الفئة</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category', $site->category) }}" placeholder="مثال: مكتبة رقمية">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>الشعار (صورة)</label>
                    @if($site->logo_url)
                        <div class="mb-2"><img src="{{ $site->logo_url }}" alt="" style="width:60px;height:60px;object-fit:contain;border-radius:8px;border:1px solid #eee;"></div>
                    @endif
                    <input type="file" name="logo" class="form-control-file" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                    <small class="text-muted">PNG / JPG / WEBP / SVG — حتى 2 ميجابايت.</small>
                </div>
                <div class="form-group col-md-3">
                    <label>الترتيب</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $site->sort_order ?? 0) }}" min="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="opens_new_tab" value="0">
                        <input type="checkbox" class="custom-control-input" id="opens_new_tab" name="opens_new_tab" value="1" {{ old('opens_new_tab', $site->opens_new_tab ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="opens_new_tab">يفتح في تبويب جديد</label>
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $site->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">مفعّل (يظهر للمستخدمين)</label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><x-svg-icon name="check-lg" :size="16" /> {{ $isEdit ? 'حفظ التعديلات' : 'إضافة الموقع' }}</button>
                <a href="{{ route('admin.educational-sites.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
