@extends('layouts.app')

@section('title', 'تقرير الصف - ' . $class->name)
@section('body_class', 'theme-light')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">تقرير الصف: {{ $class->name }}</h2>
    <div class="breadcrumb-wrapper d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
            <li class="breadcrumb-item active">{{ $class->section->name }} - {{ $academicYear?->name }}</li>
        </ol>
        <div class="d-flex align-items-center" style="gap:.4rem">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <x-svg-icon name="arrow-right" :size="14" class="me-1" /> العودة
            </a>
            <form action="{{ route('admin.reports.class-report-pdf') }}" method="GET" class="d-inline">
                <input type="hidden" name="class_id" value="{{ $class->id }}">
                <input type="hidden" name="academic_year_id" value="{{ $academicYear?->id }}">
                <input type="hidden" name="subject_id" value="{{ $subject?->id }}">
                <button type="submit" class="btn btn-danger btn-sm">
                    <x-svg-icon name="file-earmark-pdf" :size="14" class="me-1" /> تصدير PDF
                </button>
            </form>
        </div>
    </div>
</div>

<div class="content-body">
    {{-- إحصائيات الصف --}}
    @php
        $classStats = [
            ['v' => $studentsData->count(), 'l' => 'عدد الطلاب', 'icon' => 'people', 'tone' => 'navy'],
            ['v' => round($classAverage, 1) . '%', 'l' => 'معدل الصف', 'icon' => 'mortarboard', 'tone' => 'success'],
            ['v' => round($classAttendanceRate, 1) . '%', 'l' => 'نسبة الحضور', 'icon' => 'calendar-check', 'tone' => 'info'],
            ['v' => $studentsData->where('average', '>=', 50)->count(), 'l' => 'عدد الناجحين', 'icon' => 'check-circle', 'tone' => 'warning'],
        ];
    @endphp
    <div class="row mb-4">
        @foreach($classStats as $s)
            <div class="col-md-3 col-6 mb-3">
                <div class="ds-card card ds-stat h-100">
                    <div class="ds-card-body card-body d-flex align-items-center" style="gap:.7rem">
                        <span class="ds-badge-{{ $s['tone'] }}" style="width:44px;height:44px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                            <x-svg-icon name="{{ $s['icon'] }}" :size="21" />
                        </span>
                        <div>
                            <div style="font-size:1.45rem;font-weight:800;color:#0f172a;line-height:1">{{ $s['v'] }}</div>
                            <div class="text-muted small">{{ $s['l'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($subject)
        <div class="alert alert-info d-flex align-items-center" style="gap:.5rem">
            <x-svg-icon name="info-circle" :size="18" />
            <span>يتم عرض النتائج للمادة: <strong>{{ $subject->name }}</strong></span>
        </div>
    @endif

    {{-- جدول الطلاب --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title mb-0">ترتيب الطلاب</h5>
            <span class="ds-badge-navy">{{ $studentsData->count() }} طالب</span>
        </div>
        @if($studentsData->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover ds-table-tight mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px;">الترتيب</th>
                            <th>اسم الطالب</th>
                            <th class="text-center">المعدل</th>
                            <th class="text-center">نسبة الحضور</th>
                            <th class="text-center">@lang('common.status')</th>
                            <th class="text-center">@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($studentsData as $index => $data)
                            <tr>
                                <td class="text-center">
                                    @if($index < 3)
                                        <span class="ds-badge-{{ $index == 0 ? 'gold' : ($index == 1 ? 'navy' : 'warning') }}">{{ $index + 1 }}</span>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td style="font-weight:600">{{ $data['student']->name }}</td>
                                <td class="text-center">
                                    <span class="ds-badge-{{ $data['average'] >= 90 ? 'success' : ($data['average'] >= 70 ? 'info' : ($data['average'] >= 50 ? 'warning' : 'danger')) }}">{{ $data['average'] }}%</span>
                                </td>
                                <td class="text-center">
                                    <span class="ds-badge-{{ $data['attendance_rate'] >= 90 ? 'success' : ($data['attendance_rate'] >= 75 ? 'info' : 'warning') }}">{{ $data['attendance_rate'] }}%</span>
                                </td>
                                <td class="text-center">
                                    @if($data['average'] >= 50)
                                        <span class="ds-badge-success">ناجح</span>
                                    @else
                                        <span class="ds-badge-danger">راسب</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.reports.student-card', ['student_id' => $data['student']->id, 'academic_year_id' => $academicYear?->id]) }}" class="ds-action-btn" title="بطاقة الطالب" aria-label="بطاقة الطالب">
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
                <div class="ds-empty-icon"><x-svg-icon name="people" :size="30" /></div>
                <div class="ds-empty-title">لا يوجد طلاب مسجلين في هذا الصف</div>
            </div>
        @endif
    </div>
</div>
@endsection
