@extends('layouts.app')

@section('title', 'تفاصيل السنة الدراسية')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">{{ $academicYear->name }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.academic-years.index') }}">السنوات الدراسية</a></li>
                        <li class="breadcrumb-item active">التفاصيل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.academic-years.edit', $academicYear) }}" class="btn btn-warning"><i data-feather="edit"></i> تعديل</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">معلومات السنة الدراسية</h4></div>
                <div class="card-body">
                    <table class="table">
                        <tr><th>@lang('common.name')</th><td>{{ $academicYear->name }}</td></tr>
                        <tr><th>المدرسة</th><td>{{ $academicYear->school->name ?? '-' }}</td></tr>
                        <tr><th>تاريخ البداية</th><td>{{ $academicYear->start_date->format('Y-m-d') }}</td></tr>
                        <tr><th>تاريخ النهاية</th><td>{{ $academicYear->end_date->format('Y-m-d') }}</td></tr>
                        <tr><th>@lang('common.status')</th><td>@if($academicYear->is_current)<span class="badge bg-success">السنة الحالية</span>@else<span class="badge bg-secondary">سنة سابقة</span>@endif</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">الفصول ({{ $academicYear->classes->count() }})</h4></div>
                <div class="card-body">
                    @if($academicYear->classes->count() > 0)
                        <ul class="list-group">
                            @foreach($academicYear->classes as $class)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $class->name }} - {{ $class->division }}
                                    <span class="badge bg-primary">{{ $class->section->name ?? '-' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">لا توجد فصول</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
