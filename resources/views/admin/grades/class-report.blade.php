@extends('layouts.admin')

@section('title', 'تقرير الصف')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تقرير الصف</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.grades.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i>
                إدخال الدرجات
            </a>
            <a href="{{ route('admin.grades.student-report') }}" class="btn btn-outline-info">
                <i class="bi bi-person-lines-fill me-1"></i>
                تقرير الطالب
            </a>
        </div>
    </div>

    {{-- Selection Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الصف <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select" required>
                        <option value="">اختر الصف</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label class="form-label">الفصل <span class="text-danger">*</span></label>
                    <select name="semester" class="form-select" required>
                        <option value="">اختر الفصل</option>
                        @foreach(\App\Models\Grade::SEMESTERS as $key => $label)
                            <option value="{{ $key }}" {{ request('semester') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
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
                    <h5 class="mb-0">تقرير {{ $report['class']->name }}</h5>
                    <small class="text-muted">{{ $report['academic_year']->name }} - {{ \App\Models\Grade::SEMESTERS[$report['semester']] }}</small>
                </div>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    طباعة
                </button>
            </div>
            <div class="card-body">
                @if(count($report['students']) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>الترتيب</th>
                                    <th>اسم الطالب</th>
                                    @foreach($report['subjects'] as $subject)
                                        <th class="text-center">{{ $subject->name }}</th>
                                    @endforeach
                                    <th class="text-center">المعدل</th>
                                    <th class="text-center">التقدير</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['students'] as $data)
                                    <tr>
                                        <td>
                                            @if($data['rank'] <= 3)
                                                <span class="badge bg-{{ $data['rank'] == 1 ? 'warning' : ($data['rank'] == 2 ? 'secondary' : 'dark') }}">
                                                    {{ $data['rank'] }}
                                                </span>
                                            @else
                                                {{ $data['rank'] }}
                                            @endif
                                        </td>
                                        <td>{{ $data['student']->name }}</td>
                                        @foreach($report['subjects'] as $subject)
                                            @php
                                                $grade = collect($data['grades'])->firstWhere('subject_id', $subject->id);
                                            @endphp
                                            <td class="text-center {{ $grade && $grade->total < 60 ? 'text-danger' : '' }}">
                                                {{ $grade ? number_format($grade->total, 1) : '-' }}
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold">
                                            {{ number_format($data['average'], 1) }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $letterGrade = match(true) {
                                                    $data['average'] >= 95 => 'A+',
                                                    $data['average'] >= 90 => 'A',
                                                    $data['average'] >= 85 => 'B+',
                                                    $data['average'] >= 80 => 'B',
                                                    $data['average'] >= 75 => 'C+',
                                                    $data['average'] >= 70 => 'C',
                                                    $data['average'] >= 65 => 'D+',
                                                    $data['average'] >= 60 => 'D',
                                                    default => 'F',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $data['average'] >= 60 ? 'success' : 'danger' }}">
                                                {{ $letterGrade }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary --}}
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>إحصائيات الصف</h6>
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td>عدد الطلاب:</td>
                                            <td class="text-end">{{ count($report['students']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>المعدل العام:</td>
                                            <td class="text-end">{{ number_format(collect($report['students'])->avg('average'), 1) }}</td>
                                        </tr>
                                        <tr>
                                            <td>أعلى معدل:</td>
                                            <td class="text-end text-success">{{ number_format(collect($report['students'])->max('average'), 1) }}</td>
                                        </tr>
                                        <tr>
                                            <td>أقل معدل:</td>
                                            <td class="text-end text-danger">{{ number_format(collect($report['students'])->min('average'), 1) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard-x display-1 text-muted"></i>
                        <p class="mt-3 text-muted">لا توجد درجات منشورة لهذا الصف</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
