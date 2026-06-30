@extends('layouts.app')
@section('body_class','theme-light')
@section('title','ربط الطلاب')
@section('content')
<div class="content-header row"><div class="content-header-left col-md-7 mb-2">
    <h2 class="content-header-title mb-0">ربط الطلاب بمجموعات الحضور</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.qr.cards.index') }}">بطاقات QR</a></li><li class="breadcrumb-item active">ربط الطلاب</li></ol>
</div>
<div class="content-header-right col-md-5 text-md-right">
    <a href="{{ route('admin.qr.groups.index') }}" class="btn btn-outline-secondary btn-sm"><x-svg-icon name="collection" /> المجموعات</a>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-3 mb-2"><label>الفصل</label><select name="class_id" class="form-control"><option value="">— الكل —</option>@foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)request('class_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><label>المجموعة الحالية</label><select name="group_id" class="form-control"><option value="">— الكل —</option>@foreach($groups as $g)<option value="{{ $g->id }}" {{ (string)request('group_id')===(string)$g->id?'selected':'' }}>{{ $g->title }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><label>اسم الطالب</label><input type="text" name="name" value="{{ request('name') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><x-svg-icon name="search" /> بحث</button> <a href="{{ route('admin.qr.link.index') }}" class="btn btn-outline-secondary">إعادة تعيين</a></div>
    </form></div></div>

    @if($students->isEmpty())
    <div class="card"><div class="card-body"><div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="link-45deg" :size="32" /></div><div class="ds-empty-title">لا توجد نتائج</div><div class="ds-empty-desc">ابحث بالفصل أو اسم الطالب لعرض الطلاب وربطهم بمجموعة حضور.</div></div></div></div>
    @else
    <form method="POST" action="{{ route('admin.qr.link.assign') }}">@csrf
        <div class="card mb-3"><div class="card-body"><div class="form-row align-items-end">
            <div class="col-md-5 mb-2"><label>المجموعة المراد الربط بها</label>
                <select name="group_id" class="form-control">
                    <option value="">— إلغاء الربط من أي مجموعة —</option>
                    @foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->title }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2"><button type="submit" class="btn btn-primary"><x-svg-icon name="link-45deg" /> ربط الطلاب المحددين</button></div>
        </div></div></div>

        <div class="card"><div class="card-body table-responsive">
        <table class="table table-hover align-middle"><thead><tr>
            <th style="width:36px"><input type="checkbox" id="qr-link-all"></th>
            <th>الطالب</th><th>الفصل</th><th>حالة البطاقة</th><th>المجموعة الحالية</th>
        </tr></thead>
        <tbody>@foreach($students as $s)@php $card=$cardsByStudent[$s->id]??null; @endphp
        <tr>
            <td><input type="checkbox" class="qr-link-row" name="student_ids[]" value="{{ $s->id }}"></td>
            <td>{{ $s->name }}</td>
            <td>{{ optional($s->classRoom)->name ?? '—' }}</td>
            <td>
                @if(!$card)<span class="badge badge-light">لا توجد بطاقة</span>
                @elseif($card->is_active)<span class="badge badge-success">مفعّلة</span>
                @else<span class="badge badge-secondary">معطلة</span>@endif
            </td>
            <td>@if($card && $card->group)<span class="badge badge-info">{{ $card->group->title }}</span>@else<span class="text-muted">—</span>@endif</td>
        </tr>
        @endforeach</tbody></table>
        {{ $students->links() }}
        </div></div>
    </form>
    @endif
</div>
@endsection
@push('scripts')
<script>
(function () {
    var all = document.getElementById('qr-link-all');
    if (all) all.addEventListener('change', function () {
        document.querySelectorAll('.qr-link-row').forEach(function (cb) { cb.checked = all.checked; });
    });
})();
</script>
@endpush
