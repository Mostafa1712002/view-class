@extends('layouts.app')
@section('body_class','theme-light')
@section('title','تقرير السلوك')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">تقرير السلوك</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.attendance.reports.index') }}">التقارير</a></li><li class="breadcrumb-item active">السلوك</li></ol>
</div></div>
<div class="content-body">
    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <input type="hidden" name="show" value="1">
        <div class="col-md-3 mb-2"><label>من تاريخ</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><label>إلى تاريخ</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><label>اسم الطالب</label><input type="text" name="name" value="{{ request('name') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><i class="la la-eye"></i> عرض التقرير</button></div>
    </form></div></div>
    <div class="card"><div class="card-body table-responsive">
        @if($rows->isEmpty())<div class="text-center text-muted py-5"><i class="la la-star la-3x d-block mb-2"></i> اختر فلتراً لعرض سجلات السلوك.</div>
        @else
        <table class="table table-hover align-middle"><thead><tr><th>الطالب</th><th>السلوك</th><th>الإجراء</th><th>النقاط</th><th>الملاحظة</th><th>سجّله</th><th>التاريخ</th></tr></thead>
        <tbody>@foreach($rows as $r)<tr>
            <td>{{ optional($r->subject)->name ?? '—' }}</td>
            <td>{{ optional($r->behavior)->name ?? '—' }}</td>
            <td>{{ optional($r->action)->name ?? '—' }}</td>
            <td><span class="badge badge-{{ $r->points >= 0 ? 'success':'danger' }}">{{ $r->points }}</span></td>
            <td class="small">{{ \Illuminate\Support\Str::limit($r->note,30)?:'—' }}</td>
            <td>{{ optional($r->recorder)->name ?? '—' }}</td>
            <td>{{ $r->created_at?->format('Y-m-d') }}</td>
        </tr>@endforeach</tbody></table>
        {{ $rows->links() }}
        @endif
    </div></div>
</div>
@endsection
