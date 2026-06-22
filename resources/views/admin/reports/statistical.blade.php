@extends('layouts.app')

@section('title', 'التقارير الإحصائية')
@section('body_class', 'theme-light')

@php
    $cards = [
        ['icon' => 'clipboard-data', 'label' => 'تقرير الواجبات', 'tone' => 'navy'],
        ['icon' => 'easel', 'label' => 'تقرير المعلمين', 'tone' => 'info', 'link' => route('admin.reports.user-reports', ['tab' => 'teachers'])],
        ['icon' => 'camera-video', 'label' => 'تقرير الفصول الافتراضية', 'tone' => 'success'],
        ['icon' => 'chat-dots', 'label' => 'تقرير غرف النقاش', 'tone' => 'warning'],
        ['icon' => 'book-half', 'label' => 'تحضير الدروس', 'tone' => 'navy'],
        ['icon' => 'patch-question', 'label' => 'عدد الأسئلة', 'tone' => 'danger'],
        ['icon' => 'puzzle', 'label' => 'المحتويات التفاعلية', 'tone' => 'gold'],
    ];
@endphp

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">التقارير الإحصائية</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">التقارير الإحصائية</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('admin.reports._tabs', ['currentTab' => 'statistical'])

    <div class="alert alert-info d-flex align-items-center" style="gap:.5rem">
        <x-svg-icon name="info-circle" :size="18" />
        <span>التقارير الإحصائية تُمكّن من المقارنة التفاعلية بين المدارس في أداء الأنشطة والمهام.</span>
    </div>

    <div class="row">
        @foreach($cards as $c)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="ds-card ds-card-accent card h-100">
                    <div class="ds-card-body card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-2" style="gap:.6rem">
                            <span class="ds-badge-{{ $c['tone'] }}" style="width:42px;height:42px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                                <x-svg-icon name="{{ $c['icon'] }}" :size="20" />
                            </span>
                            <h5 class="ds-card-title mb-0" style="font-size:1rem">{{ $c['label'] }}</h5>
                        </div>
                        <p class="text-muted small flex-grow-1">{{ isset($c['link']) ? 'متاح للعرض' : 'قيد التطوير' }}</p>
                        @if(isset($c['link']))
                            <a href="{{ $c['link'] }}" class="btn btn-primary btn-sm align-self-start">
                                <x-svg-icon name="eye" :size="14" class="me-1" /> عرض
                            </a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm align-self-start" disabled>
                                <x-svg-icon name="eye" :size="14" class="me-1" /> قريبًا
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
