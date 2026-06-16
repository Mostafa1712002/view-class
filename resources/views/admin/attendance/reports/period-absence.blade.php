@extends('layouts.app')
@section('body_class','theme-light')
@section('title','تقرير غياب حصص')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">تقرير غياب حصص</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.attendance.reports.index') }}">التقارير</a></li><li class="breadcrumb-item active">غياب حصص</li></ol>
</div></div>
<div class="content-body">
    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-3 mb-2"><label>التاريخ</label><input type="date" name="date" value="{{ request('date') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><label>الفصل</label><select name="class_id" class="form-control"><option value="">— الكل —</option>@foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)request('class_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><label>المادة</label><select name="subject_id" class="form-control"><option value="">— الكل —</option>@foreach($subjects as $s)<option value="{{ $s->id }}" {{ (string)request('subject_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><x-svg-icon name="eye" /> عرض التقرير</button></div>
    </form></div></div>
    @if(!$rows->isEmpty()) @include('admin.attendance.reports._export-buttons', ['report' => 'period-absence']) @endif
    <div class="card"><div class="card-body table-responsive">
        @if($rows->isEmpty())<div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="clock" :size="32" /></div><div class="ds-empty-title">لا توجد بيانات</div><div class="ds-empty-desc">اختر فلتراً لعرض البيانات.</div></div>
        @else
        <table class="table table-hover align-middle"><thead><tr><th>الطالب</th><th>الفصل</th><th>المادة</th><th>الحصة</th><th>التاريخ</th></tr></thead>
        <tbody>@foreach($rows as $r)<tr>
            <td>{{ optional($r->student)->name ?? '—' }}</td><td>{{ optional($r->classRoom)->name ?? '—' }}</td>
            <td>{{ optional($r->subject)->name ?? '—' }}</td><td>{{ $r->period }}</td><td>{{ $r->date?->format('Y-m-d') }}</td>
        </tr>@endforeach</tbody></table>
        {{ $rows->links() }}
        @endif
    </div></div>
</div>
@endsection
