@extends('layouts.app')

@section('title', 'التقارير الإدارية')
@section('body_class', 'theme-light')

@php
    $cards = [
        ['icon' => 'building', 'title' => 'تقرير المدارس العام', 'desc' => 'إحصائية شاملة لكل مدرسة: عدد الطلاب، المعلمين، الفصول.', 'link' => route('admin.reports.schools-general'), 'tone' => 'navy'],
        ['icon' => 'calendar-x', 'title' => 'غياب الأيام بحسب الفصول', 'desc' => 'تقرير غياب الطلاب بحسب الفصول الدراسية.', 'link' => route('admin.reports.attendance-report'), 'tone' => 'warning'],
        ['icon' => 'clock-history', 'title' => 'غياب الحصص بحسب الفصول', 'desc' => 'قيد التطوير.', 'link' => null, 'tone' => 'info'],
        ['icon' => 'book', 'title' => 'ملخص غيابات المواد الدراسية', 'desc' => 'قيد التطوير.', 'link' => null, 'tone' => 'success'],
        ['icon' => 'journal-text', 'title' => 'تقرير المواد العام', 'desc' => 'قيد التطوير.', 'link' => null, 'tone' => 'gold'],
    ];
@endphp

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">التقارير الإدارية</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">التقارير الإدارية</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('admin.reports._tabs', ['currentTab' => 'administrative'])

    <div class="row">
        @foreach($cards as $c)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="ds-card ds-card-accent card h-100">
                    <div class="ds-card-body card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-2" style="gap:.6rem">
                            <span class="ds-badge-{{ $c['tone'] }}" style="width:42px;height:42px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                                <x-svg-icon name="{{ $c['icon'] }}" :size="20" />
                            </span>
                            <h5 class="ds-card-title mb-0" style="font-size:1rem">{{ $c['title'] }}</h5>
                        </div>
                        <p class="text-muted small flex-grow-1">{{ $c['desc'] }}</p>
                        @if($c['link'])
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
