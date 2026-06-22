@extends('layouts.app')

@section('title', 'لوحة الإحصائيات')
@section('body_class', 'theme-light')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">لوحة الإحصائيات</h2>
    <div class="breadcrumb-wrapper d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
            <li class="breadcrumb-item active">لوحة الإحصائيات</li>
        </ol>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="arrow-right" :size="14" class="me-1" /> العودة
        </a>
    </div>
</div>

<div class="content-body">
    @if($academicYear?->name)
        <p class="text-muted mb-3">{{ $academicYear->name }}</p>
    @endif

    {{-- إحصائيات عامة --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="ds-card card ds-stat h-100">
                <div class="ds-card-body card-body d-flex align-items-center" style="gap:.9rem">
                    <span class="ds-badge-navy" style="width:48px;height:48px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                        <x-svg-icon name="people" :size="24" />
                    </span>
                    <div>
                        <div style="font-size:1.7rem;font-weight:800;color:#0f172a;line-height:1">{{ $overallStats['total_students'] }}</div>
                        <div class="text-muted small">إجمالي الطلاب</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="ds-card card ds-stat h-100">
                <div class="ds-card-body card-body d-flex align-items-center" style="gap:.9rem">
                    <span class="ds-badge-success" style="width:48px;height:48px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                        <x-svg-icon name="mortarboard" :size="24" />
                    </span>
                    <div>
                        <div style="font-size:1.7rem;font-weight:800;color:#0f172a;line-height:1">{{ $overallStats['average_grades'] }}%</div>
                        <div class="text-muted small">متوسط الدرجات</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="ds-card card ds-stat h-100">
                <div class="ds-card-body card-body d-flex align-items-center" style="gap:.9rem">
                    <span class="ds-badge-info" style="width:48px;height:48px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                        <x-svg-icon name="calendar-check" :size="24" />
                    </span>
                    <div>
                        <div style="font-size:1.7rem;font-weight:800;color:#0f172a;line-height:1">{{ $overallStats['average_attendance'] }}%</div>
                        <div class="text-muted small">متوسط الحضور</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- أفضل الصفوف --}}
        <div class="col-md-6 mb-4">
            <div class="ds-card card h-100">
                <div class="ds-card-header card-header">
                    <h5 class="ds-card-title mb-0 d-flex align-items-center" style="gap:.4rem">
                        <x-svg-icon name="trophy" :size="18" /> أفضل الصفوف أداءً
                    </h5>
                </div>
                @if($topClasses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover ds-table-tight mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الصف</th>
                                    <th class="text-center">@lang('common.students')</th>
                                    <th class="text-center">المعدل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topClasses as $index => $data)
                                    <tr>
                                        <td>
                                            @if($index == 0)<span class="ds-badge-gold">🥇</span>
                                            @elseif($index == 1)<span class="ds-badge-navy">🥈</span>
                                            @elseif($index == 2)<span class="ds-badge-warning">🥉</span>
                                            @else {{ $index + 1 }}@endif
                                        </td>
                                        <td>{{ $data['class']->name }}</td>
                                        <td class="text-center">{{ $data['students_count'] }}</td>
                                        <td class="text-center"><span class="ds-badge-success">{{ $data['grades_average'] }}%</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ds-empty">
                        <div class="ds-empty-icon"><x-svg-icon name="bar-chart-line" :size="28" /></div>
                        <div class="ds-empty-title">لا توجد بيانات</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- الصفوف التي تحتاج تحسين --}}
        <div class="col-md-6 mb-4">
            <div class="ds-card card h-100">
                <div class="ds-card-header card-header">
                    <h5 class="ds-card-title mb-0 d-flex align-items-center" style="gap:.4rem">
                        <x-svg-icon name="exclamation-triangle" :size="18" /> صفوف تحتاج تحسين
                    </h5>
                </div>
                @if($bottomClasses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover ds-table-tight mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الصف</th>
                                    <th class="text-center">@lang('common.students')</th>
                                    <th class="text-center">المعدل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bottomClasses as $index => $data)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $data['class']->name }}</td>
                                        <td class="text-center">{{ $data['students_count'] }}</td>
                                        <td class="text-center">
                                            <span class="ds-badge-{{ $data['grades_average'] >= 50 ? 'warning' : 'danger' }}">{{ $data['grades_average'] }}%</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ds-empty">
                        <div class="ds-empty-icon"><x-svg-icon name="bar-chart-line" :size="28" /></div>
                        <div class="ds-empty-title">لا توجد بيانات</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- جدول جميع الصفوف --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title mb-0">إحصائيات جميع الصفوف</h5>
        </div>
        @if($classStats->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover ds-table-tight mb-0">
                    <thead>
                        <tr>
                            <th>الصف</th>
                            <th>@lang('common.section')</th>
                            <th class="text-center">عدد الطلاب</th>
                            <th class="text-center">معدل الدرجات</th>
                            <th class="text-center">نسبة الحضور</th>
                            <th class="text-center">@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classStats->sortByDesc('grades_average') as $data)
                            <tr>
                                <td>{{ $data['class']->name }}</td>
                                <td>{{ $data['class']->section->name ?? '-' }}</td>
                                <td class="text-center">{{ $data['students_count'] }}</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $data['grades_average'] >= 70 ? 'success' : ($data['grades_average'] >= 50 ? 'warning' : 'danger') }}"
                                             style="width: {{ $data['grades_average'] }}%">{{ $data['grades_average'] }}%</div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $data['attendance_rate'] >= 80 ? 'success' : ($data['attendance_rate'] >= 60 ? 'info' : 'warning') }}"
                                             style="width: {{ $data['attendance_rate'] }}%">{{ $data['attendance_rate'] }}%</div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.reports.class-report', ['class_id' => $data['class']->id]) }}" class="ds-action-btn" title="تفاصيل" aria-label="تفاصيل">
                                        <x-svg-icon name="eye" :size="15" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="bar-chart-line" :size="30" /></div>
                <div class="ds-empty-title">لا توجد صفوف</div>
            </div>
        @endif
    </div>
</div>
@endsection
