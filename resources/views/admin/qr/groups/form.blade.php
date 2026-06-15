@extends('layouts.app')
@section('body_class','theme-light')
@section('title', $group->exists ? 'تعديل مجموعة' : 'إنشاء مجموعة')
@section('content')
@php $days=[0=>'الأحد',1=>'الاثنين',2=>'الثلاثاء',3=>'الأربعاء',4=>'الخميس',5=>'الجمعة',6=>'السبت']; $selDays=$group->work_days??[]; @endphp
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">{{ $group->exists ? 'تعديل مجموعة حضور' : 'إنشاء مجموعة حضور' }}</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.qr.groups.index') }}">المجموعات</a></li><li class="breadcrumb-item active">{{ $group->exists ? 'تعديل' : 'إنشاء' }}</li></ol>
</div></div>
<div class="content-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $group->exists ? route('admin.qr.groups.update',$group->id) : route('admin.qr.groups.store') }}">
        @csrf
        @if($group->exists)@method('PUT')@endif
        <div class="card"><div class="card-body">
            <div class="form-row">
                <div class="col-md-6 form-group"><label>العنوان *</label><input type="text" name="title" value="{{ old('title',$group->title) }}" class="form-control" required></div>
                <div class="col-md-6 form-group"><label>العنوان بالإنجليزية</label><input type="text" name="title_en" value="{{ old('title_en',$group->title_en) }}" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="col-md-3 form-group"><label>الحالة الافتراضية</label>
                    <select name="default_status" class="form-control">
                        @foreach(['present'=>'حاضر','late'=>'متأخر','absent'=>'غائب','excused'=>'مستأذن'] as $k=>$v)<option value="{{ $k }}" {{ old('default_status',$group->default_status)===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group"><label>بداية الحضور</label><input type="time" name="present_start" value="{{ old('present_start',$group->present_start) }}" class="form-control"></div>
                <div class="col-md-3 form-group"><label>بداية التأخير</label><input type="time" name="late_start" value="{{ old('late_start',$group->late_start) }}" class="form-control"></div>
                <div class="col-md-3 form-group"><label>بداية الغياب</label><input type="time" name="absent_start" value="{{ old('absent_start',$group->absent_start) }}" class="form-control"></div>
            </div>
            <div class="form-row">
                <div class="col-md-3 form-group"><label>بداية الاستئذان</label><input type="time" name="excuse_start" value="{{ old('excuse_start',$group->excuse_start) }}" class="form-control"></div>
            </div>
            <div class="form-group">
                <label>أيام العمل</label><br>
                @foreach($days as $i=>$d)
                <label class="mr-3"><input type="checkbox" name="work_days[]" value="{{ $i }}" {{ in_array($i,$selDays)?'checked':'' }}> {{ $d }}</label>
                @endforeach
            </div>
            <div class="form-group"><label>الوصف</label><textarea name="description" rows="2" class="form-control">{{ old('description',$group->description) }}</textarea></div>
            <div class="form-group form-check"><input type="checkbox" name="is_active" value="1" id="isActive" class="form-check-input" {{ old('is_active',$group->exists?$group->is_active:true)?'checked':'' }}><label for="isActive" class="form-check-label">مفعّل</label></div>
        </div>
        <div class="card-footer text-left">
            <button type="submit" class="btn btn-primary"><i class="la la-save"></i> حفظ</button>
            <a href="{{ route('admin.qr.groups.index') }}" class="btn btn-secondary">إلغاء</a>
        </div></div>
    </form>
</div>
@endsection
