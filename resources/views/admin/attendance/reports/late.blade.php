@extends('layouts.app')
@section('body_class','theme-light')
@section('title','تقرير التأخير')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">تقرير التأخير</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.attendance.reports.index') }}">التقارير</a></li><li class="breadcrumb-item active">التأخير</li></ol>
</div></div>
<div class="content-body">
    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-3 mb-2"><label>من تاريخ</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><label>إلى تاريخ</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-md-2 mb-2"><label>الفصل</label><select name="class_id" class="form-control"><option value="">— الكل —</option>@foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)request('class_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-2 mb-2"><label>نوع التأخير</label><select name="late_type" class="form-control"><option value="">الكل</option><option value="late_day" {{ request('late_type')==='late_day'?'selected':'' }}>تأخير يوم</option><option value="late_period" {{ request('late_type')==='late_period'?'selected':'' }}>تأخير حصة</option></select></div>
        <div class="col-md-2 mb-2"><button class="btn btn-primary"><i class="la la-eye"></i> عرض</button></div>
    </form></div></div>
    <div class="card"><div class="card-body table-responsive">
        @if($rows->isEmpty())<div class="text-center text-muted py-5"><i class="la la-hourglass-half la-3x d-block mb-2"></i> اختر فترة لعرض البيانات.</div>
        @else
        <table class="table table-hover align-middle"><thead><tr><th>الطالب</th><th>رقم الهوية</th><th>الفصل</th><th>الحصة</th><th>وقت الحضور</th><th>التاريخ</th></tr></thead>
        <tbody>@foreach($rows as $r)<tr>
            <td>{{ optional($r->student)->name ?? '—' }}</td><td>{{ optional($r->student)->national_id ?? '—' }}</td>
            <td>{{ optional($r->classRoom)->name ?? '—' }}</td><td>{{ $r->period ?? 'يومي' }}</td><td>{{ $r->arrival_time ?? '—' }}</td><td>{{ $r->date?->format('Y-m-d') }}</td>
        </tr>@endforeach</tbody></table>
        {{ $rows->links() }}
        @endif
    </div></div>
</div>
@endsection
