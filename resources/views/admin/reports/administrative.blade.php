@extends('layouts.app')

@section('title', 'التقارير الإدارية')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">التقارير</h2>
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
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="la la-school text-primary"></i> تقرير المدارس العام</h5>
                    <p class="card-text small text-muted">إحصائية شاملة لكل مدرسة: عدد الطلاب، المعلمين، الفصول.</p>
                    <a href="{{ route('admin.reports.schools-general') }}" class="btn btn-primary btn-sm"><i class="la la-eye"></i> عرض</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="la la-calendar-times text-warning"></i> غياب الأيام بحسب الفصول</h5>
                    <p class="card-text small text-muted">تقرير غياب الطلاب بحسب الفصول الدراسية.</p>
                    <a href="{{ route('admin.reports.attendance-report') }}" class="btn btn-outline-primary btn-sm"><i class="la la-eye"></i> عرض</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="la la-clock text-info"></i> غياب الحصص بحسب الفصول</h5>
                    <p class="card-text small text-muted">قيد التطوير.</p>
                    <button class="btn btn-outline-secondary btn-sm" disabled><i class="la la-eye"></i> عرض</button>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="la la-book-open text-success"></i> ملخص غيابات المواد الدراسية</h5>
                    <p class="card-text small text-muted">قيد التطوير.</p>
                    <button class="btn btn-outline-secondary btn-sm" disabled><i class="la la-eye"></i> عرض</button>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="la la-book text-purple"></i> تقرير المواد العام</h5>
                    <p class="card-text small text-muted">قيد التطوير.</p>
                    <button class="btn btn-outline-secondary btn-sm" disabled><i class="la la-eye"></i> عرض</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
