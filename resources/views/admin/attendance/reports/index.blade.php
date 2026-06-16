@extends('layouts.app')
@section('body_class','theme-light')
@section('title', 'تقارير الحضور والغياب')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 mb-2">
        <h2 class="content-header-title mb-0">تقارير الحضور والغياب والسلوك</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">التقارير</li>
        </ol>
    </div>
</div>
<div class="content-body">
    <div class="row">
        @php
        $cards = [
            ['حالة الحضور','admin.attendance.reports.status','clipboard-check','primary'],
            ['غياب أيام','admin.attendance.reports.day-absence','calendar-x','danger'],
            ['غياب حصص','admin.attendance.reports.period-absence','clock','warning'],
            ['التأخير','admin.attendance.reports.late','hourglass-split','info'],
            ['الغياب المجمع','admin.attendance.reports.aggregate','bar-chart','success'],
            ['السلوك','admin.attendance.reports.behavior','star','secondary'],
        ];
        @endphp
        @foreach($cards as [$lbl,$route,$ic,$col])
        <div class="col-md-3 col-6 mb-3">
            <a href="{{ route($route) }}" class="text-decoration-none">
                <div class="card border-{{ $col }} h-100"><div class="card-body text-center py-4">
                    <x-svg-icon :name="$ic" :size="40" class="text-{{ $col }} mb-2 d-block" />
                    <h5 class="mb-0">{{ $lbl }}</h5>
                </div></div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection
