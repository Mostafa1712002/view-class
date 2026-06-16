@extends('layouts.app')
@section('body_class','theme-light')
@section('title','سجل مسحات QR')
@section('content')
@php $labels=['present'=>'حاضر','late'=>'متأخر','absent'=>'غائب','excused'=>'مستأذن','rejected'=>'مرفوض']; $colors=['present'=>'success','late'=>'warning','absent'=>'danger','excused'=>'info','rejected'=>'dark']; @endphp
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">سجل مسحات QR</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.qr.cards.index') }}">بطاقات QR</a></li><li class="breadcrumb-item active">سجل المسحات</li></ol>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-2 mb-2"><label>التاريخ</label><input type="date" name="date" value="{{ request('date') }}" class="form-control"></div>
        <div class="col-md-2 mb-2"><label>الحالة</label><select name="status" class="form-control"><option value="">— الكل —</option>@foreach($labels as $k=>$v)<option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2 mb-2"><label>القناة</label><select name="channel" class="form-control"><option value="">— الكل —</option><option value="camera" {{ request('channel')==='camera'?'selected':'' }}>كاميرا</option><option value="manual" {{ request('channel')==='manual'?'selected':'' }}>يدوي</option><option value="iot" {{ request('channel')==='iot'?'selected':'' }}>IoT</option></select></div>
        <div class="col-md-3 mb-2"><label>اسم الطالب</label><input type="text" name="name" value="{{ request('name') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><x-svg-icon name="search" /> بحث</button> <a href="{{ route('admin.qr.log') }}" class="btn btn-outline-secondary">إعادة تعيين</a></div>
    </form></div></div>

    <div class="card mb-3"><div class="card-body table-responsive">
        @if($scans->isEmpty())<div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="list-ul" :size="32" /></div><div class="ds-empty-title">لا توجد مسحات</div><div class="ds-empty-desc">لا توجد مسحات مطابقة لمعايير البحث الحالية.</div></div>
        @else
        <table class="table table-hover align-middle"><thead><tr><th>الطالب</th><th>الفصل</th><th>وقت المسح</th><th>الحالة الناتجة</th><th>القناة</th><th>الجهاز</th><th>الخطأ</th></tr></thead>
        <tbody>@foreach($scans as $s)<tr>
            <td>{{ optional($s->student)->name ?? '—' }}</td>
            <td>{{ optional(optional($s->student)->classRoom)->name ?? '—' }}</td>
            <td>{{ $s->scanned_at?->format('Y-m-d H:i') }}</td>
            <td><span class="badge badge-{{ $colors[$s->result_status]??'secondary' }}">{{ $labels[$s->result_status]??$s->result_status }}</span></td>
            <td>{{ ['camera'=>'كاميرا','manual'=>'يدوي','iot'=>'IoT'][$s->channel]??$s->channel }}</td>
            <td>{{ $s->device_name ?? '—' }}</td>
            <td class="small text-danger">{{ $s->error_code ?? '—' }}</td>
        </tr>@endforeach</tbody></table>
        {{ $scans->links() }}
        @endif
    </div></div>

    {{-- Day close --}}
    <div class="card border-warning"><div class="card-body">
        <h5 class="mb-3"><x-svg-icon name="lock" /> إغلاق اليوم</h5>
        <form method="POST" action="{{ route('admin.qr.close-day') }}" class="form-row align-items-end" onsubmit="return confirm('سيتم منع تسجيل مسحات جديدة وتسجيل غير الممسوحين كغياب. متابعة؟');">
            @csrf
            <div class="col-md-3 mb-2"><label>تاريخ التحضير</label><input type="date" name="close_date" value="{{ now()->format('Y-m-d') }}" class="form-control" required></div>
            <div class="col-md-3 mb-2"><label>الفصل</label><select name="class_id" class="form-control"><option value="">— كل الفصول —</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-md-3 mb-2"><div class="form-check"><input type="checkbox" name="confirm" value="1" id="confirmClose" class="form-check-input" required><label for="confirmClose" class="form-check-label">أؤكد إغلاق اليوم</label></div></div>
            <div class="col-md-3 mb-2"><button class="btn btn-warning"><x-svg-icon name="lock" /> تنفيذ إغلاق اليوم</button></div>
        </form>
    </div></div>
</div>
@endsection
