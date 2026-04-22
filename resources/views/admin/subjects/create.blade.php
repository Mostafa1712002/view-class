@extends('layouts.app')

@section('title', 'إضافة مادة')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">إضافة مادة</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.subjects.index') }}">المواد</a></li>
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
        <div class="card-body">
            <form action="{{ route('manage.subjects.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">اسم المادة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">رمز المادة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if(Auth::user()->isSuperAdmin())
                    <div class="col-md-6 mb-3">
                        <label for="school_id" class="form-label">المدرسة <span class="text-danger">*</span></label>
                        <select class="form-select @error('school_id') is-invalid @enderror" id="school_id" name="school_id" required>
                            <option value="">اختر المدرسة</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @else
                    <input type="hidden" name="school_id" value="{{ Auth::user()->school_id }}">
                    @endif
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch mt-4">
                            <input type="hidden" name="is_core" value="0">
                            <input type="checkbox" class="form-check-input" id="is_core" name="is_core" value="1" {{ old('is_core') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_core">مادة أساسية</label>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">الوصف</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i data-feather="save"></i> @lang('common.save')</button>
                    <a href="{{ route('manage.subjects.index') }}" class="btn btn-secondary"><i data-feather="x"></i> إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
