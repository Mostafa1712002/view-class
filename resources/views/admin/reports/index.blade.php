@extends('layouts.admin')

@section('title', 'التقارير')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">التقارير</h1>
    </div>

    <div class="row">
        {{-- بطاقة الطالب --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary rounded-circle p-3 me-3">
                            <i class="la la-user-graduate text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">بطاقة الطالب</h5>
                            <small class="text-muted">تقرير شامل عن أداء الطالب</small>
                        </div>
                    </div>
                    <p class="text-muted mb-3">عرض درجات الطالب وحضوره في جميع المواد مع إمكانية التصدير PDF</p>
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
                            <i class="la la-search me-1"></i>عرض التقرير
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- تقرير الصف --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success rounded-circle p-3 me-3">
                            <i class="la la-users text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">تقرير الصف</h5>
                            <small class="text-muted">مقارنة أداء طلاب الصف</small>
                        </div>
                    </div>
                    <p class="text-muted mb-3">عرض ترتيب الطلاب ومعدلاتهم ونسب الحضور مع المقارنة</p>
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
                        <button type="submit" class="btn btn-success w-100">
                            <i class="la la-search me-1"></i>عرض التقرير
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- تقرير الحضور الشهري --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info rounded-circle p-3 me-3">
                            <i class="la la-calendar-check text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">تقرير الحضور الشهري</h5>
                            <small class="text-muted">إحصائيات الحضور الشهرية</small>
                        </div>
                    </div>
                    <p class="text-muted mb-3">عرض تفاصيل الحضور والغياب لكل طالب خلال شهر محدد</p>
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
                        <button type="submit" class="btn btn-info w-100">
                            <i class="la la-search me-1"></i>عرض التقرير
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- لوحة الإحصائيات --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning rounded-circle p-3 me-3">
                            <i class="la la-chart-bar text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">لوحة الإحصائيات</h5>
                            <small class="text-muted">نظرة شاملة على الأداء</small>
                        </div>
                    </div>
                    <p class="text-muted mb-3">عرض إحصائيات عامة عن جميع الصفوف ومقارنة الأداء</p>
                    <a href="{{ route('admin.reports.analytics') }}" class="btn btn-warning w-100">
                        <i class="la la-chart-line me-1"></i>عرض الإحصائيات
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
