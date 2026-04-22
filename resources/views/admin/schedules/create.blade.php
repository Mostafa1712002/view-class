@extends('layouts.app')

@section('title', 'إضافة جدول جديد')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">إضافة جدول جديد</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.schedules.index') }}">الجداول الدراسية</a></li>
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
        <div class="card-header"><h4 class="card-title">بيانات الجدول</h4></div>
        <div class="card-body">
            <form action="{{ route('manage.schedules.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-1">
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
                    <div class="col-md-4 mb-1">
                        <label class="form-label">السنة الدراسية <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-control @error('academic_year_id') is-invalid @enderror" required>
                            <option value="">اختر السنة</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                    {{ $year->name }} @if($year->is_current) (الحالية) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-1">
                        <label class="form-label">الفصل الدراسي <span class="text-danger">*</span></label>
                        <select name="semester" class="form-control @error('semester') is-invalid @enderror" required>
                            <option value="">اختر</option>
                            <option value="first" {{ old('semester') == 'first' ? 'selected' : '' }}>الفصل الأول</option>
                            <option value="second" {{ old('semester') == 'second' ? 'selected' : '' }}>الفصل الثاني</option>
                        </select>
                        @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-2">
                    <button type="submit" class="btn btn-primary">إنشاء الجدول</button>
                    <a href="{{ route('manage.schedules.index') }}" class="btn btn-secondary">@lang('common.cancel')</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
