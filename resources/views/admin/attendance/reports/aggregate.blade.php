@extends('layouts.app')
@section('body_class','theme-light')
@section('title','الغياب المجمع')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">الغياب المجمع</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.attendance.reports.index') }}">التقارير</a></li><li class="breadcrumb-item active">الغياب المجمع</li></ol>
</div></div>
<div class="content-body">
    <div class="card mb-3"><div class="card-body"><form method="GET" class="form-row align-items-end">
        <div class="col-md-3 mb-2"><label>التاريخ</label><input type="date" name="date" value="{{ $date }}" class="form-control"></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary"><x-svg-icon name="eye" /> عرض</button></div>
    </form></div></div>

    <div class="row mb-3">
        @foreach([['الكل','all','dark'],['حضور','present','success'],['غياب','absent','danger'],['تأخير','late','warning'],['استئذان','excused','info']] as [$lbl,$k,$col])
        <div class="col-md col-6 mb-2"><div class="card border-{{ $col }}"><div class="card-body py-2 text-center">
            <div class="text-muted small">{{ $lbl }}</div><h3 class="mb-0 text-{{ $col }}">{{ $totals[$k] }}</h3>
        </div></div></div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-6"><div class="card"><div class="card-body">
            <h5 class="mb-3">توزيع الحالات</h5>
            @php $sum = max(1, $totals['all']); @endphp
            @foreach([['حضور','present','success'],['غياب','absent','danger'],['تأخير','late','warning'],['استئذان','excused','info']] as [$lbl,$k,$col])
            @php $pct = round($totals[$k]/$sum*100); @endphp
            <div class="mb-2">
                <div class="d-flex justify-content-between small"><span>{{ $lbl }}</span><span>{{ $totals[$k] }} ({{ $pct }}%)</span></div>
                <div class="progress" style="height:14px"><div class="progress-bar bg-{{ $col }}" style="width:{{ $pct }}%"></div></div>
            </div>
            @endforeach
        </div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body">
            <h5 class="mb-3">الغياب حسب الصفوف</h5>
            @if($byClass->isEmpty())<p class="text-muted text-center py-4">لا يوجد غياب في هذا التاريخ.</p>
            @else
                @php $maxClass = max(1, $byClass->max()); @endphp
                @foreach($byClass as $cname => $cnt)
                <div class="mb-2">
                    <div class="d-flex justify-content-between small"><span>{{ $cname }}</span><span>{{ $cnt }}</span></div>
                    <div class="progress" style="height:14px"><div class="progress-bar bg-danger" style="width:{{ round($cnt/$maxClass*100) }}%"></div></div>
                </div>
                @endforeach
            @endif
        </div></div></div>
    </div>
</div>
@endsection
