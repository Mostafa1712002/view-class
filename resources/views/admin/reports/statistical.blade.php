@extends('layouts.app')

@section('title', 'التقارير الإحصائية')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">التقارير</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">التقارير الإحصائية</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('admin.reports._tabs', ['currentTab' => 'statistical'])

    <div class="alert alert-info">
        <i class="la la-info-circle"></i>
        التقارير الإحصائية تُمكّن من المقارنة التفاعلية بين المدارس في أداء الأنشطة والمهام.
    </div>

    <div class="row">
        @php
            $cards = [
                ['icon' => 'tasks', 'label' => 'تقرير الواجبات', 'color' => 'primary'],
                ['icon' => 'chalkboard-teacher', 'label' => 'تقرير المعلمين', 'color' => 'info', 'link' => route('admin.reports.user-reports', ['tab' => 'teachers'])],
                ['icon' => 'video', 'label' => 'تقرير الفصول الافتراضية', 'color' => 'success'],
                ['icon' => 'comments', 'label' => 'تقرير غرف النقاش', 'color' => 'warning'],
                ['icon' => 'book-reader', 'label' => 'تحضير الدروس', 'color' => 'secondary'],
                ['icon' => 'question-circle', 'label' => 'عدد الأسئلة', 'color' => 'danger'],
                ['icon' => 'puzzle-piece', 'label' => 'المحتويات التفاعلية', 'color' => 'dark'],
            ];
        @endphp
        @foreach($cards as $c)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="la la-{{ $c['icon'] }} text-{{ $c['color'] }}"></i> {{ $c['label'] }}</h5>
                        <p class="card-text small text-muted">{{ isset($c['link']) ? 'متاح للعرض' : 'قيد التطوير' }}</p>
                        @if(isset($c['link']))
                            <a href="{{ $c['link'] }}" class="btn btn-outline-{{ $c['color'] }} btn-sm"><i class="la la-eye"></i> عرض</a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" disabled><i class="la la-eye"></i> عرض</button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
