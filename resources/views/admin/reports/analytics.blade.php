@extends('layouts.admin')

@section('title', 'لوحة الإحصائيات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">لوحة الإحصائيات</h1>
            <small class="text-muted">{{ $academicYear?->name }}</small>
        </div>
    </div>

    {{-- إحصائيات عامة --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-4">
                    <i class="la la-users display-4 mb-2"></i>
                    <h2 class="mb-0">{{ $overallStats['total_students'] }}</h2>
                    <p class="mb-0">إجمالي الطلاب</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-4">
                    <i class="la la-graduation-cap display-4 mb-2"></i>
                    <h2 class="mb-0">{{ $overallStats['average_grades'] }}%</h2>
                    <p class="mb-0">متوسط الدرجات</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-4">
                    <i class="la la-calendar-check display-4 mb-2"></i>
                    <h2 class="mb-0">{{ $overallStats['average_attendance'] }}%</h2>
                    <p class="mb-0">متوسط الحضور</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- أفضل الصفوف --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="la la-trophy me-2"></i>أفضل الصفوف أداءً</h5>
                </div>
                <div class="card-body">
                    @if($topClasses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
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
                                                @if($index == 0)
                                                    <span class="badge bg-warning">🥇</span>
                                                @elseif($index == 1)
                                                    <span class="badge bg-secondary">🥈</span>
                                                @elseif($index == 2)
                                                    <span class="badge bg-danger">🥉</span>
                                                @else
                                                    {{ $index + 1 }}
                                                @endif
                                            </td>
                                            <td>{{ $data['class']->name }}</td>
                                            <td class="text-center">{{ $data['students_count'] }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-success fs-6">{{ $data['grades_average'] }}%</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="la la-chart-bar display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد بيانات</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- الصفوف التي تحتاج تحسين --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="la la-exclamation-triangle me-2"></i>صفوف تحتاج تحسين</h5>
                </div>
                <div class="card-body">
                    @if($bottomClasses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
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
                                                <span class="badge bg-{{ $data['grades_average'] >= 50 ? 'warning' : 'danger' }} fs-6">
                                                    {{ $data['grades_average'] }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="la la-chart-bar display-4 text-muted"></i>
                            <p class="text-muted mt-2">لا توجد بيانات</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- جدول جميع الصفوف --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">إحصائيات جميع الصفوف</h5>
        </div>
        <div class="card-body">
            @if($classStats->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
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
                                                 style="width: {{ $data['grades_average'] }}%">
                                                {{ $data['grades_average'] }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $data['attendance_rate'] >= 80 ? 'success' : ($data['attendance_rate'] >= 60 ? 'info' : 'warning') }}"
                                                 style="width: {{ $data['attendance_rate'] }}%">
                                                {{ $data['attendance_rate'] }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.reports.class-report', ['class_id' => $data['class']->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="la la-eye"></i> تفاصيل
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="la la-chart-bar display-4 text-muted"></i>
                    <p class="text-muted mt-2">لا توجد صفوف</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
