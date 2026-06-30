@extends('layouts.app')
@section('body_class','theme-light')
@section('title','أجهزة IoT')
@section('content')
<div class="content-header row"><div class="content-header-left col-md-7 mb-2">
    <h2 class="content-header-title mb-0">أجهزة IoT — ماسحات QR</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.qr.cards.index') }}">بطاقات QR</a></li><li class="breadcrumb-item active">أجهزة IoT</li></ol>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <h5 class="mb-2">تسجيل جهاز جديد</h5>
        <form method="POST" action="{{ route('admin.qr.devices.store') }}" class="form-row align-items-end">@csrf
            <div class="col-md-4 mb-2"><label>اسم الجهاز</label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="مثال: بوابة المدخل الرئيسي"></div>
            <div class="col-md-4 mb-2"><label>الموقع (اختياري)</label><input type="text" name="location" value="{{ old('location') }}" class="form-control" placeholder="مثال: المبنى أ"></div>
            <div class="col-md-3 mb-2"><button class="btn btn-primary"><x-svg-icon name="plus-lg" /> تسجيل الجهاز</button></div>
        </form>
    </div></div>

    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-4 mb-2"><label>اسم الجهاز</label><input type="text" name="name" value="{{ request('name') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><label>الحالة</label><select name="status" class="form-control"><option value="">— الكل —</option><option value="1" {{ request('status')==='1'?'selected':'' }}>مفعّل</option><option value="0" {{ request('status')==='0'?'selected':'' }}>غير مفعّل</option></select></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><x-svg-icon name="search" /> بحث</button> <a href="{{ route('admin.qr.devices.index') }}" class="btn btn-outline-secondary">إعادة تعيين</a></div>
    </form></div></div>

    <div class="card"><div class="card-body table-responsive">
        @if($devices->isEmpty())<div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="cpu" :size="32" /></div><div class="ds-empty-title">لا توجد أجهزة مسجّلة</div><div class="ds-empty-desc">سجّل جهاز ماسح QR جديد من النموذج أعلاه.</div></div>
        @else
        <table class="table table-hover align-middle"><thead><tr><th>الاسم</th><th>الموقع</th><th>مفتاح الجهاز</th><th>آخر اتصال</th><th>الحالة</th><th>التحكم</th></tr></thead>
        <tbody>@foreach($devices as $d)<tr>
            <td>{{ $d->name }}</td>
            <td>{{ $d->location ?? '—' }}</td>
            <td><code>{{ $d->device_key }}</code></td>
            <td>{{ optional($d->last_seen_at)?->format('Y-m-d H:i') ?? '—' }}</td>
            <td>@if($d->is_active)<span class="badge badge-success">مفعّل</span>@else<span class="badge badge-secondary">معطل</span>@endif</td>
            <td>
                <form method="POST" action="{{ route('admin.qr.devices.toggle', $d->id) }}" class="d-inline">@csrf
                    <button class="btn btn-sm btn-outline-{{ $d->is_active?'warning':'success' }}" title="{{ $d->is_active?'تعطيل':'تفعيل' }}"><x-svg-icon name="power" /></button>
                </form>
                <form method="POST" action="{{ route('admin.qr.devices.regenerate', $d->id) }}" class="d-inline" onsubmit="return confirm('إعادة توليد مفتاح الجهاز؟ سيتوقف الجهاز الحالي حتى يُحدَّث مفتاحه.');">@csrf
                    <button class="btn btn-sm btn-outline-primary" title="إعادة توليد المفتاح"><x-svg-icon name="arrow-repeat" /></button>
                </form>
                <form method="POST" action="{{ route('admin.qr.devices.destroy', $d->id) }}" class="d-inline" onsubmit="return confirm('حذف الجهاز؟');">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="حذف"><x-svg-icon name="trash" /></button>
                </form>
            </td>
        </tr>@endforeach</tbody></table>
        {{ $devices->links() }}
        @endif
    </div></div>
</div>
@endsection
