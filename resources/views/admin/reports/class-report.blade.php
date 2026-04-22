@extends('layouts.admin')

@section('title', 'تقرير الصف - ' . $class->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">تقرير الصف: {{ $class->name }}</h1>
            <small class="text-muted">{{ $class->section->name }} - {{ $academicYear?->name }}</small>
        </div>
        <form action="{{ route('admin.reports.class-report-pdf') }}" method="GET" class="d-inline">
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <input type="hidden" name="academic_year_id" value="{{ $academicYear?->id }}">
            <input type="hidden" name="subject_id" value="{{ $subject?->id }}">
            <button type="submit" class="btn btn-danger">
                <i class="la la-file-pdf me-1"></i>تصدير PDF
            </button>
        </form>
    </div>

    {{-- إحصائيات الصف --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $studentsData->count() }}</h3>
                    <small>عدد الطلاب</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ round($classAverage, 1) }}%</h3>
                    <small>معدل الصف</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ round($classAttendanceRate, 1) }}%</h3>
                    <small>نسبة الحضور</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $studentsData->where('average', '>=', 50)->count() }}</h3>
                    <small>عدد الناجحين</small>
                </div>
            </div>
        </div>
    </div>

    @if($subject)
        <div class="alert alert-info">
            <i class="la la-info-circle me-1"></i>
            يتم عرض النتائج للمادة: <strong>{{ $subject->name }}</strong>
        </div>
    @endif

    {{-- جدول الطلاب --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">ترتيب الطلاب</h5>
            <span class="badge bg-secondary">{{ $studentsData->count() }} طالب</span>
        </div>
        <div class="card-body">
            @if($studentsData->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
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
                                            <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') }} rounded-circle" style="width: 30px; height: 30px; line-height: 22px;">
                                                {{ $index + 1 }}
                                            </span>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </td>
                                    <td>{{ $data['student']->name }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $data['average'] >= 90 ? 'success' : ($data['average'] >= 70 ? 'primary' : ($data['average'] >= 50 ? 'warning' : 'danger')) }} fs-6">
                                            {{ $data['average'] }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $data['attendance_rate'] >= 90 ? 'success' : ($data['attendance_rate'] >= 75 ? 'info' : 'warning') }}">
                                            {{ $data['attendance_rate'] }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($data['average'] >= 50)
                                            <span class="badge bg-success">ناجح</span>
                                        @else
                                            <span class="badge bg-danger">راسب</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.reports.student-card', ['student_id' => $data['student']->id, 'academic_year_id' => $academicYear?->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="la la-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="la la-users display-4 text-muted"></i>
                    <p class="text-muted mt-2">لا يوجد طلاب مسجلين في هذا الصف</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
