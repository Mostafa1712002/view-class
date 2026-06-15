@extends('layouts.app')
@section('title','تفاصيل طلب القبول')
@section('body_class','theme-light')

@php
    use App\Modules\Admissions\Models\AdmissionApplication;
    $statuses = AdmissionApplication::STATUSES;
@endphp

@section('content')
<div class="content-header">
    <h2>طلب القبول {{ $application->code }}</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">القبول والتسجيل</a></li>
        <li class="breadcrumb-item active">{{ $application->code }}</li>
    </ol>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row">
        <div class="col-lg-7 mb-3">
            <div class="card"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">بيانات الطلب</h5>
                    <span class="badge badge-{{ $application->statusColor() }} p-2">{{ $application->statusLabel() }}</span>
                </div>
                <table class="table table-sm">
                    <tr><th width="35%">اسم الطالب</th><td>{{ $application->student_name ?: '—' }}</td></tr>
                    <tr><th>اسم ولي الأمر</th><td>{{ $application->guardian_name ?: '—' }}</td></tr>
                    <tr><th>الجوال</th><td dir="ltr">{{ $application->phone ?: '—' }}</td></tr>
                    <tr><th>البريد</th><td dir="ltr">{{ $application->email ?: '—' }}</td></tr>
                    <tr><th>الهوية</th><td>{{ $application->national_id ?: '—' }}</td></tr>
                    <tr><th>الكود الهجري</th><td>{{ $application->hijri_code ?: '—' }}</td></tr>
                    <tr><th>تاريخ الميلاد</th><td>{{ optional($application->birth_date)->format('Y-m-d') ?: '—' }}</td></tr>
                    <tr><th>الجنسية</th><td>{{ $application->nationality ?: '—' }}</td></tr>
                    <tr><th>المدينة</th><td>{{ $application->city ?: '—' }}</td></tr>
                    <tr><th>المرحلة / الصف</th><td>{{ trim(($application->stage ?: '').' '.($application->grade ?: '')) ?: '—' }}</td></tr>
                    <tr><th>العنوان</th><td>{{ $application->address ?: '—' }}</td></tr>
                    <tr><th>الموعد</th><td>{{ $application->appointment_at ? $application->appointment_at->format('Y-m-d H:i') : '—' }}</td></tr>
                    @if($application->status_note)
                    <tr><th>ملاحظة الحالة</th><td>{{ $application->status_note }}</td></tr>
                    @endif
                </table>

                @if(!empty($application->data))
                <h6 class="mt-3">حقول إضافية</h6>
                <table class="table table-sm">
                    @foreach($application->data as $k => $v)
                        <tr><th width="35%">{{ $k }}</th><td>{{ is_array($v) ? implode(', ', $v) : ($v ?: '—') }}</td></tr>
                    @endforeach
                </table>
                @endif

                <div class="mt-3 d-flex gap-2">
                    <a href="{{ route('admissions.edit', $application->id) }}" class="btn btn-outline-secondary btn-sm">
                        <x-svg-icon name="pencil" :size="15" /> تعديل
                    </a>
                    <a href="{{ route('admissions.print', $application->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <x-svg-icon name="printer" :size="15" /> طباعة
                    </a>
                    <form method="POST" action="{{ route('admissions.destroy', $application->id) }}" onsubmit="return confirm('تأكيد حذف الطلب؟')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm"><x-svg-icon name="trash" :size="15" /> حذف</button>
                    </form>
                </div>
            </div></div>
        </div>

        <div class="col-lg-5">
            {{-- change status --}}
            <div class="card mb-3"><div class="card-body">
                <h6>تغيير الحالة</h6>
                <form method="POST" action="{{ route('admissions.status', $application->id) }}">
                    @csrf
                    <select name="status" class="form-control mb-2">
                        @foreach($statuses as $key => $meta)
                            <option value="{{ $key }}" {{ $application->status===$key?'selected':'' }}>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                    <textarea name="note" class="form-control mb-2" rows="2" placeholder="ملاحظة (اختياري)">{{ $application->status_note }}</textarea>
                    <button class="btn btn-primary btn-sm"><x-svg-icon name="check2" :size="15" /> حفظ الحالة</button>
                </form>
            </div></div>

            {{-- schedule --}}
            <div class="card mb-3"><div class="card-body">
                <h6>تحديد موعد</h6>
                <form method="POST" action="{{ route('admissions.schedule', $application->id) }}" class="d-flex gap-2">
                    @csrf
                    <input type="datetime-local" name="appointment_at" class="form-control"
                           value="{{ optional($application->appointment_at)->format('Y-m-d\TH:i') }}" required>
                    <button class="btn btn-outline-primary btn-sm"><x-svg-icon name="calendar-event" :size="15" /></button>
                </form>
            </div></div>

            {{-- message --}}
            <div class="card mb-3"><div class="card-body">
                <h6>إرسال رسالة</h6>
                <form method="POST" action="{{ route('admissions.message', $application->id) }}">
                    @csrf
                    <textarea name="message" class="form-control mb-2" rows="2" placeholder="نص الرسالة" required></textarea>
                    <button class="btn btn-outline-primary btn-sm"><x-svg-icon name="send" :size="15" /> إرسال</button>
                </form>
            </div></div>

            {{-- convert to student --}}
            <div class="card"><div class="card-body">
                <h6>تحويل إلى طالب</h6>
                @if($application->converted_student_id)
                    <div class="alert alert-success mb-0">تم تحويل هذا الطلب إلى طالب مسبقًا.</div>
                @else
                <form method="POST" action="{{ route('admissions.convert', $application->id) }}">
                    @csrf
                    <label class="small">الفصل</label>
                    <select name="class_room_id" class="form-control mb-2">
                        <option value="">— بدون فصل —</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    <label class="small">القسم</label>
                    <select name="section_id" class="form-control mb-2">
                        <option value="">— بدون قسم —</option>
                        @foreach($sections as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                    <button class="btn btn-success btn-sm" onclick="return confirm('سيتم إنشاء حساب طالب وولي أمر. متابعة؟')">
                        <x-svg-icon name="person-plus" :size="15" /> تحويل إلى طالب
                    </button>
                </form>
                @endif
            </div></div>
        </div>
    </div>
</div>
@endsection
