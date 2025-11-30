@extends('layouts.app')

@section('title', 'إضافة قسم')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">إضافة قسم</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.sections.index') }}">الأقسام</a></li>
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
            <form action="{{ route('manage.sections.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">اسم القسم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(Auth::user()->isSuperAdmin())
                    <div class="col-md-6 mb-3">
                        <label for="school_id" class="form-label">المدرسة <span class="text-danger">*</span></label>
                        <select class="form-select @error('school_id') is-invalid @enderror" id="school_id" name="school_id" required>
                            <option value="">اختر المدرسة</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @else
                        <input type="hidden" name="school_id" value="{{ Auth::user()->school_id }}">
                    @endif

                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">الجنس <span class="text-danger">*</span></label>
                        <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                            <option value="">اختر الجنس</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>بنين</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>بنات</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="level" class="form-label">المرحلة <span class="text-danger">*</span></label>
                        <select class="form-select @error('level') is-invalid @enderror" id="level" name="level" required>
                            <option value="">اختر المرحلة</option>
                            <option value="primary" {{ old('level') == 'primary' ? 'selected' : '' }}>ابتدائي</option>
                            <option value="intermediate" {{ old('level') == 'intermediate' ? 'selected' : '' }}>متوسط</option>
                            <option value="secondary" {{ old('level') == 'secondary' ? 'selected' : '' }}>ثانوي</option>
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">الوصف</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save"></i> حفظ
                    </button>
                    <a href="{{ route('manage.sections.index') }}" class="btn btn-secondary">
                        <i data-feather="x"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
