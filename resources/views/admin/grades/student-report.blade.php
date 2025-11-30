@extends('layouts.admin')

@section('title', 'تقرير الطالب')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تقرير الطالب</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.grades.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i>
                إدخال الدرجات
            </a>
            <a href="{{ route('admin.grades.class-report') }}" class="btn btn-outline-info">
                <i class="bi bi-file-earmark-bar-graph me-1"></i>
                تقرير الصف
            </a>
        </div>
    </div>

    {{-- Selection Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">الطالب <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">اختر الطالب</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">العام الدراسي <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="form-select" required>
                        <option value="">اختر العام</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>
                        عرض التقرير
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Report --}}
    @if($report)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">تقرير الطالب: {{ $report['student']->name }}</h5>
                    <small class="text-muted">{{ $report['academic_year']->name }}</small>
                </div>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    طباعة
                </button>
            </div>
            <div class="card-body">
                {{-- Summary Cards --}}
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h2 class="mb-0">{{ number_format($report['overall_average'] ?? 0, 1) }}</h2>
                                <small>المعدل العام</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h2 class="mb-0">{{ ($report['first_semester']['passed'] ?? 0) + ($report['second_semester']['passed'] ?? 0) }}</h2>
                                <small>مواد ناجح فيها</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h2 class="mb-0">{{ ($report['first_semester']['failed'] ?? 0) + ($report['second_semester']['failed'] ?? 0) }}</h2>
                                <small>مواد راسب فيها</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                @php
                                    $letterGrade = match(true) {
                                        ($report['overall_average'] ?? 0) >= 95 => 'A+',
                                        ($report['overall_average'] ?? 0) >= 90 => 'A',
                                        ($report['overall_average'] ?? 0) >= 85 => 'B+',
                                        ($report['overall_average'] ?? 0) >= 80 => 'B',
                                        ($report['overall_average'] ?? 0) >= 75 => 'C+',
                                        ($report['overall_average'] ?? 0) >= 70 => 'C',
                                        ($report['overall_average'] ?? 0) >= 65 => 'D+',
                                        ($report['overall_average'] ?? 0) >= 60 => 'D',
                                        default => 'F',
                                    };
                                @endphp
                                <h2 class="mb-0">{{ $letterGrade }}</h2>
                                <small>التقدير العام</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- First Semester --}}
                <h5 class="mb-3">الفصل الأول</h5>
                @if($report['first_semester']['grades']->count() > 0)
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>المادة</th>
                                    <th class="text-center">الاختبارات</th>
                                    <th class="text-center">الواجبات</th>
                                    <th class="text-center">النصفي</th>
                                    <th class="text-center">النهائي</th>
                                    <th class="text-center">المشاركة</th>
                                    <th class="text-center">المجموع</th>
                                    <th class="text-center">التقدير</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['first_semester']['grades'] as $grade)
                                    <tr>
                                        <td>{{ $grade->subject->name ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->quiz_avg ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->homework_avg ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->midterm ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->final ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->participation ?? '-' }}</td>
                                        <td class="text-center fw-bold {{ $grade->total < 60 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($grade->total, 1) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $grade->total >= 60 ? 'success' : 'danger' }}">
                                                {{ $grade->letter_grade }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-start">المعدل</th>
                                    <th class="text-center">{{ number_format($report['first_semester']['average'] ?? 0, 1) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info mb-4">لا توجد درجات منشورة للفصل الأول</div>
                @endif

                {{-- Second Semester --}}
                <h5 class="mb-3">الفصل الثاني</h5>
                @if($report['second_semester']['grades']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>المادة</th>
                                    <th class="text-center">الاختبارات</th>
                                    <th class="text-center">الواجبات</th>
                                    <th class="text-center">النصفي</th>
                                    <th class="text-center">النهائي</th>
                                    <th class="text-center">المشاركة</th>
                                    <th class="text-center">المجموع</th>
                                    <th class="text-center">التقدير</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['second_semester']['grades'] as $grade)
                                    <tr>
                                        <td>{{ $grade->subject->name ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->quiz_avg ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->homework_avg ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->midterm ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->final ?? '-' }}</td>
                                        <td class="text-center">{{ $grade->participation ?? '-' }}</td>
                                        <td class="text-center fw-bold {{ $grade->total < 60 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($grade->total, 1) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $grade->total >= 60 ? 'success' : 'danger' }}">
                                                {{ $grade->letter_grade }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-start">المعدل</th>
                                    <th class="text-center">{{ number_format($report['second_semester']['average'] ?? 0, 1) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">لا توجد درجات منشورة للفصل الثاني</div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
