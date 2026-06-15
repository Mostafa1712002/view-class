@extends('layouts.app')
@section('title','تعديل طلب القبول')
@section('body_class','theme-light')

@section('content')
<div class="content-header">
    <h2>تعديل طلب القبول {{ $application->code }}</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">القبول والتسجيل</a></li>
        <li class="breadcrumb-item active">تعديل</li>
    </ol>
</div>

<div class="content-body">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admissions.update', $application->id) }}">
            @csrf @method('PUT')
            <div class="form-row">
                @php
                    $fields = [
                        'student_name' => 'اسم الطالب', 'guardian_name' => 'اسم ولي الأمر',
                        'phone' => 'الجوال', 'email' => 'البريد', 'national_id' => 'الهوية',
                        'hijri_code' => 'الكود الهجري', 'city' => 'المدينة', 'track' => 'المسار',
                        'stage' => 'المرحلة', 'grade' => 'الصف', 'nationality' => 'الجنسية',
                    ];
                @endphp
                @foreach($fields as $name => $label)
                <div class="col-md-4 mb-2">
                    <label>{{ $label }}</label>
                    <input type="text" name="{{ $name }}" value="{{ old($name, $application->$name) }}" class="form-control">
                </div>
                @endforeach
                <div class="col-md-4 mb-2">
                    <label>تاريخ الميلاد</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', optional($application->birth_date)->format('Y-m-d')) }}" class="form-control">
                </div>
                <div class="col-12 mb-2">
                    <label>العنوان</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $application->address) }}</textarea>
                </div>
            </div>
            <button class="btn btn-primary"><x-svg-icon name="check2" :size="16" /> حفظ</button>
            <a href="{{ route('admissions.show', $application->id) }}" class="btn btn-outline-secondary">إلغاء</a>
        </form>
    </div></div>
</div>
@endsection
