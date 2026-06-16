@extends('layouts.app')
@section('body_class','theme-light')
@section('title','بطاقات QR')
@section('content')
<div class="content-header row"><div class="content-header-left col-md-7 mb-2">
    <h2 class="content-header-title mb-0">بطاقات QR للطلاب</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li><li class="breadcrumb-item active">بطاقات QR</li></ol>
</div>
<div class="content-header-right col-md-5 text-md-right">
    <a href="{{ route('admin.qr.scanner') }}" class="btn btn-outline-primary btn-sm"><x-svg-icon name="qr-code" /> ماسح QR</a>
    <a href="{{ route('admin.qr.log') }}" class="btn btn-outline-secondary btn-sm"><x-svg-icon name="list-ul" /> سجل المسحات</a>
    <a href="{{ route('admin.qr.groups.index') }}" class="btn btn-outline-secondary btn-sm"><x-svg-icon name="collection" /> المجموعات</a>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-3 mb-2"><label>الفصل</label><select name="class_id" class="form-control"><option value="">— الكل —</option>@foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)request('class_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><label>اسم الطالب</label><input type="text" name="name" value="{{ request('name') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><label>رقم الهوية</label><input type="text" name="national_id" value="{{ request('national_id') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><x-svg-icon name="search" /> بحث</button></div>
    </form></div></div>

    <div class="card"><div class="card-body table-responsive">
        @if($students->isEmpty())<div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="person-vcard" :size="32" /></div><div class="ds-empty-title">لا توجد بطاقات معروضة</div><div class="ds-empty-desc">ابحث باسم الطالب أو الفصل لعرض الطلاب وبطاقاتهم.</div></div>
        @else
        <table class="table table-hover align-middle"><thead><tr><th>الطالب</th><th>رقم الهوية</th><th>الفصل</th><th>حالة البطاقة</th><th>كود البطاقة</th><th>تاريخ الإنشاء</th><th>التحكم</th></tr></thead>
        <tbody>@foreach($students as $s)@php $card=$cardsByStudent[$s->id]??null; @endphp
        <tr>
            <td>{{ $s->name }}</td><td>{{ $s->national_id ?? '—' }}</td><td>{{ optional($s->classRoom)->name ?? '—' }}</td>
            <td>
                @if(!$card)<span class="badge badge-light">لا توجد بطاقة</span>
                @elseif($card->is_active)<span class="badge badge-success">مفعّلة</span>
                @else<span class="badge badge-secondary">معطلة</span>@endif
            </td>
            <td>{{ $card->card_code ?? '—' }}</td>
            <td>{{ optional($card)->created_at?->format('Y-m-d') ?? '—' }}</td>
            <td>
                <form method="POST" action="{{ route('admin.qr.cards.generate') }}" class="d-inline">@csrf<input type="hidden" name="student_id" value="{{ $s->id }}">
                    <button class="btn btn-sm btn-outline-primary" title="{{ $card?'إعادة توليد':'إنشاء' }}"><x-svg-icon name="qr-code" /></button>
                </form>
                @if($card)
                <form method="POST" action="{{ route('admin.qr.cards.toggle', $card->id) }}" class="d-inline">@csrf
                    <button class="btn btn-sm btn-outline-{{ $card->is_active?'warning':'success' }}"><x-svg-icon name="power" /></button>
                </form>
                <a href="{{ route('admin.qr.cards.print', ['ids'=>[$card->id]]) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><x-svg-icon name="printer" /></a>
                @endif
            </td>
        </tr>
        @endforeach</tbody></table>
        {{ $students->links() }}
        @endif
    </div></div>
</div>
@endsection
