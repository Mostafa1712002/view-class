@extends('layouts.app')
@section('body_class','theme-light')
@section('title','تقرير حالة الحضور')
@section('content')
@php $labels=['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','excused'=>'مستأذن']; $colors=['present'=>'success','absent'=>'danger','late'=>'warning','excused'=>'info']; @endphp
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">تقرير حالة الحضور</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.attendance.reports.index') }}">التقارير</a></li><li class="breadcrumb-item active">حالة الحضور</li></ol>
</div></div>
<div class="content-body">
    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-3 mb-2"><label>التاريخ</label><input type="date" name="date" value="{{ request('date') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><label>الفصل</label><select name="class_id" class="form-control"><option value="">— الكل —</option>@foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)request('class_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><label>الحالة</label><select name="status" class="form-control"><option value="">— الكل —</option>@foreach($labels as $k=>$v)<option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><i class="la la-eye"></i> عرض التقرير</button></div>
    </form></div></div>
    @if(!$rows->isEmpty()) @include('admin.attendance.reports._export-buttons', ['report' => 'status']) @endif
    <div class="card"><div class="card-body table-responsive">
        @if($rows->isEmpty())<div class="text-center text-muted py-5"><i class="la la-file-alt la-3x d-block mb-2"></i> اختر فلتراً لعرض البيانات.</div>
        @else
        <table class="table table-hover align-middle"><thead><tr><th>الطالب</th><th>رقم الهوية</th><th>الفصل</th><th>الحالة</th><th>وقت الحضور</th><th>التاريخ</th><th>الملاحظات</th></tr></thead>
        <tbody>@foreach($rows as $r)<tr>
            <td>{{ optional($r->student)->name ?? '—' }}</td><td>{{ optional($r->student)->national_id ?? '—' }}</td>
            <td>{{ optional($r->classRoom)->name ?? '—' }}</td>
            <td><span class="badge badge-{{ $colors[$r->status]??'secondary' }}">{{ $labels[$r->status]??$r->status }}</span></td>
            <td>{{ $r->arrival_time ?? '—' }}</td><td>{{ $r->date?->format('Y-m-d') }}</td><td class="small">{{ \Illuminate\Support\Str::limit($r->notes,30)?:'—' }}</td>
        </tr>@endforeach</tbody></table>
        {{ $rows->links() }}
        @endif
    </div></div>
</div>
@endsection
