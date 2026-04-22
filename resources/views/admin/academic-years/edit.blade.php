@extends('layouts.app')

@section('title', 'تعديل سنة دراسية')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">تعديل سنة دراسية</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.academic-years.index') }}">السنوات الدراسية</a></li>
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
        <div class="card-body">
            <form action="{{ route('manage.academic-years.update', $academicYear) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">اسم السنة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $academicYear->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if(Auth::user()->isSuperAdmin())
                    <div class="col-md-6 mb-3">
                        <label for="school_id" class="form-label">المدرسة <span class="text-danger">*</span></label>
                        <select class="form-select @error('school_id') is-invalid @enderror" id="school_id" name="school_id" required>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ old('school_id', $academicYear->school_id) == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @else
                    <input type="hidden" name="school_id" value="{{ Auth::user()->school_id }}">
                    @endif
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">تاريخ البداية <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $academicYear->start_date->format('Y-m-d')) }}" required>
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">تاريخ النهاية <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $academicYear->end_date->format('Y-m-d')) }}" required>
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_current" value="0">
                            <input type="checkbox" class="form-check-input" id="is_current" name="is_current" value="1" {{ old('is_current', $academicYear->is_current) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_current">السنة الحالية</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i data-feather="save"></i> تحديث</button>
                    <a href="{{ route('manage.academic-years.index') }}" class="btn btn-secondary"><i data-feather="x"></i> إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
