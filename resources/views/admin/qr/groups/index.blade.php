@extends('layouts.app')
@section('body_class','theme-light')
@section('title','مجموعات الحضور')
@section('content')
<div class="content-header row"><div class="content-header-left col-md-7 mb-2">
    <h2 class="content-header-title mb-0">مجموعات الحضور — QR</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.qr.cards.index') }}">بطاقات QR</a></li><li class="breadcrumb-item active">المجموعات</li></ol>
</div>
<div class="content-header-right col-md-5 text-md-right"><a href="{{ route('admin.qr.groups.create') }}" class="btn btn-primary btn-sm"><x-svg-icon name="plus-lg" /> إنشاء مجموعة</a></div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-4 mb-2"><label>العنوان</label><input type="text" name="title" value="{{ request('title') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><label>الحالة</label><select name="status" class="form-control"><option value="">— الكل —</option><option value="1" {{ request('status')==='1'?'selected':'' }}>مفعّل</option><option value="0" {{ request('status')==='0'?'selected':'' }}>غير مفعّل</option></select></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><x-svg-icon name="search" /> بحث</button> <a href="{{ route('admin.qr.groups.index') }}" class="btn btn-outline-secondary">إعادة تعيين</a></div>
    </form></div></div>

    <div class="card"><div class="card-body table-responsive">
        @if($groups->isEmpty())<div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="collection" :size="32" /></div><div class="ds-empty-title">لا توجد مجموعات</div><div class="ds-empty-desc">أنشئ مجموعة جديدة.</div></div>
        @else
        <table class="table table-hover align-middle"><thead><tr><th>العنوان</th><th>بداية الحضور</th><th>بداية التأخير</th><th>بداية الغياب</th><th>الحالة</th><th>التحكم</th></tr></thead>
        <tbody>@foreach($groups as $g)<tr>
            <td>{{ $g->title }}</td><td>{{ $g->present_start ?? '—' }}</td><td>{{ $g->late_start ?? '—' }}</td><td>{{ $g->absent_start ?? '—' }}</td>
            <td>@if($g->is_active)<span class="badge badge-success">مفعّل</span>@else<span class="badge badge-secondary">معطل</span>@endif</td>
            <td>
                <a href="{{ route('admin.qr.groups.edit', $g->id) }}" class="btn btn-sm btn-outline-primary"><x-svg-icon name="pencil-square" /></a>
                <form method="POST" action="{{ route('admin.qr.groups.destroy', $g->id) }}" class="d-inline" onsubmit="return confirm('حذف المجموعة؟');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><x-svg-icon name="trash" /></button></form>
            </td>
        </tr>@endforeach</tbody></table>
        {{ $groups->links() }}
        @endif
    </div></div>
</div>
@endsection
