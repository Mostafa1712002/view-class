@extends('layouts.app')

@section('title', 'التقارير')
@section('body_class', 'theme-light')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">التقارير</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">التقارير</li>
        </ol>
    </div>
</div>

<div class="content-body">
    <div class="row">
        {{-- بطاقة الطالب --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="ds-card ds-card-accent card h-100">
                <div class="ds-card-body card-body">
                    <div class="d-flex align-items-center mb-3" style="gap:.7rem">
                        <span class="ds-badge-navy" style="width:46px;height:46px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                            <x-svg-icon name="mortarboard" :size="22" />
                        </span>
                        <div>
                            <h5 class="ds-card-title mb-0" style="font-size:1rem">بطاقة الطالب</h5>
                            <small class="text-muted">تقرير شامل عن أداء الطالب</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">عرض درجات الطالب وحضوره في جميع المواد مع إمكانية التصدير PDF</p>
                    <form action="{{ route('admin.reports.student-card') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label">الطالب</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">اختر الطالب...</option>
                                @php
                                    $students = \App\Models\User::whereHas('roles', fn($q) => $q->where('slug', 'student'))
                                        ->when(!auth()->user()->isSuperAdmin(), fn($q) => $q->where('school_id', auth()->user()->school_id))
                                        ->orderBy('name')
                                        ->get();
                                @endphp
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">العام الدراسي</label>
                            <select name="academic_year_id" class="form-select">
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <x-svg-icon name="search" :size="15" class="me-1" /> عرض التقرير
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- تقرير الصف --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="ds-card ds-card-accent card h-100">
                <div class="ds-card-body card-body">
                    <div class="d-flex align-items-center mb-3" style="gap:.7rem">
                        <span class="ds-badge-success" style="width:46px;height:46px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                            <x-svg-icon name="people" :size="22" />
                        </span>
                        <div>
                            <h5 class="ds-card-title mb-0" style="font-size:1rem">تقرير الصف</h5>
                            <small class="text-muted">مقارنة أداء طلاب الصف</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">عرض ترتيب الطلاب ومعدلاتهم ونسب الحضور مع المقارنة</p>
                    <form action="{{ route('admin.reports.class-report') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label">الصف</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">اختر الصف...</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">المادة (اختياري)</label>
                            <select name="subject_id" class="form-select">
                                <option value="">جميع المواد</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">العام الدراسي</label>
                            <select name="academic_year_id" class="form-select">
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <x-svg-icon name="search" :size="15" class="me-1" /> عرض التقرير
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- تقرير الحضور الشهري --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="ds-card ds-card-accent card h-100">
                <div class="ds-card-body card-body">
                    <div class="d-flex align-items-center mb-3" style="gap:.7rem">
                        <span class="ds-badge-info" style="width:46px;height:46px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                            <x-svg-icon name="calendar-check" :size="22" />
                        </span>
                        <div>
                            <h5 class="ds-card-title mb-0" style="font-size:1rem">تقرير الحضور الشهري</h5>
                            <small class="text-muted">إحصائيات الحضور الشهرية</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">عرض تفاصيل الحضور والغياب لكل طالب خلال شهر محدد</p>
                    <form action="{{ route('admin.reports.attendance-report') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label">الصف</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">اختر الصف...</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الشهر</label>
                            <input type="month" name="month" class="form-control" value="{{ now()->format('Y-m') }}">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <x-svg-icon name="search" :size="15" class="me-1" /> عرض التقرير
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- لوحة الإحصائيات --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="ds-card ds-card-accent card h-100">
                <div class="ds-card-body card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3" style="gap:.7rem">
                        <span class="ds-badge-gold" style="width:46px;height:46px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                            <x-svg-icon name="bar-chart-line" :size="22" />
                        </span>
                        <div>
                            <h5 class="ds-card-title mb-0" style="font-size:1rem">لوحة الإحصائيات</h5>
                            <small class="text-muted">نظرة شاملة على الأداء</small>
                        </div>
                    </div>
                    <p class="text-muted small mb-3 flex-grow-1">عرض إحصائيات عامة عن جميع الصفوف ومقارنة الأداء</p>
                    <a href="{{ route('admin.reports.analytics') }}" class="btn btn-primary w-100">
                        <x-svg-icon name="graph-up" :size="15" class="me-1" /> عرض الإحصائيات
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
