@extends('layouts.app')

@section('title', 'إضافة فصل')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">إضافة فصل</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.classes.index') }}">الفصول</a></li>
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
            <form action="{{ route('manage.classes.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">اسم الفصل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="section_id" class="form-label">القسم <span class="text-danger">*</span></label>
                        <select class="form-select @error('section_id') is-invalid @enderror" id="section_id" name="section_id" required>
                            <option value="">اختر القسم</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                            @endforeach
                        </select>
                        @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="academic_year_id" class="form-label">السنة الدراسية <span class="text-danger">*</span></label>
                        <select class="form-select @error('academic_year_id') is-invalid @enderror" id="academic_year_id" name="academic_year_id" required>
                            <option value="">اختر السنة</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="grade_level" class="form-label">الصف <span class="text-danger">*</span></label>
                        <select class="form-select @error('grade_level') is-invalid @enderror" id="grade_level" name="grade_level" required>
                            <option value="">اختر الصف</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ old('grade_level') == $i ? 'selected' : '' }}>الصف {{ $i }}</option>
                            @endfor
                        </select>
                        @error('grade_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="division" class="form-label">الشعبة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('division') is-invalid @enderror" id="division" name="division" value="{{ old('division') }}" placeholder="أ، ب، ج..." required>
                        @error('division')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="capacity" class="form-label">السعة <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', 30) }}" min="1" max="100" required>
                        @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="room" class="form-label">رقم الغرفة</label>
                        <input type="text" class="form-control @error('room') is-invalid @enderror" id="room" name="room" value="{{ old('room') }}">
                        @error('room')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i data-feather="save"></i> @lang('common.save')</button>
                    <a href="{{ route('manage.classes.index') }}" class="btn btn-secondary"><i data-feather="x"></i> إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
