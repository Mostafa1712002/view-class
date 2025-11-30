@extends('layouts.admin')

@section('title', 'بطاقة الطالب - ' . $student->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">بطاقة الطالب</h1>
        </div>
        <form action="{{ route('admin.reports.student-card-pdf') }}" method="GET" class="d-inline">
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <input type="hidden" name="academic_year_id" value="{{ $academicYear?->id }}">
            <button type="submit" class="btn btn-danger">
                <i class="la la-file-pdf me-1"></i>تصدير PDF
            </button>
        </form>
    </div>

    {{-- معلومات الطالب --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 text-center">
                    <div class="avatar avatar-xl mb-3">
                        <span class="avatar-content bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2rem;">
                            {{ mb_substr($student->name, 0, 1) }}
                        </span>
                    </div>
                </div>
                <div class="col-md-10">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">اسم الطالب</label>
                            <p class="mb-0 fw-bold">{{ $student->name }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">البريد الإلكتروني</label>
                            <p class="mb-0">{{ $student->email }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">العام الدراسي</label>
                            <p class="mb-0">{{ $academicYear?->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">الصف</label>
                            <p class="mb-0">{{ $enrollment?->classRoom?->name ?? 'غير مسجل' }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">المرحلة</label>
                            <p class="mb-0">{{ $enrollment?->classRoom?->section?->name ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- إحصائيات سريعة --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $grades->count() }}</h3>
                    <small>عدد المواد</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    @php
                        $overallAverage = $grades->count() > 0 ? round($grades->avg('average'), 1) : 0;
                    @endphp
                    <h3 class="mb-0">{{ $overallAverage }}%</h3>
                    <small>المعدل العام</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $attendanceStats['rate'] }}%</h3>
                    <small>نسبة الحضور</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $attendanceStats['absent'] }}</h3>
                    <small>أيام الغياب</small>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول الدرجات --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">الدرجات حسب المادة</h5>
        </div>
        <div class="card-body">
            @if($grades->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>المادة</th>
                                <th class="text-center">الفترة الأولى</th>
                                <th class="text-center">الفترة الثانية</th>
                                <th class="text-center">الفترة الثالثة</th>
                                <th class="text-center">الفترة الرابعة</th>
                                <th class="text-center">المعدل</th>
                                <th class="text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grades as $subjectData)
                                <tr>
                                    <td>{{ $subjectData['subject']->name }}</td>
                                    <td class="text-center">{{ $subjectData['terms']->get('الفترة الأولى')?->total ?? '-' }}</td>
                                    <td class="text-center">{{ $subjectData['terms']->get('الفترة الثانية')?->total ?? '-' }}</td>
                                    <td class="text-center">{{ $subjectData['terms']->get('الفترة الثالثة')?->total ?? '-' }}</td>
                                    <td class="text-center">{{ $subjectData['terms']->get('الفترة الرابعة')?->total ?? '-' }}</td>
                                    <td class="text-center fw-bold">{{ $subjectData['average'] }}%</td>
                                    <td class="text-center">
                                        @if($subjectData['average'] >= 50)
                                            <span class="badge bg-success">ناجح</span>
                                        @else
                                            <span class="badge bg-danger">راسب</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>المعدل العام</th>
                                <th colspan="5" class="text-center">{{ $overallAverage }}%</th>
                                <th class="text-center">
                                    @if($overallAverage >= 50)
                                        <span class="badge bg-success">ناجح</span>
                                    @else
                                        <span class="badge bg-danger">راسب</span>
                                    @endif
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="la la-graduation-cap display-4 text-muted"></i>
                    <p class="text-muted mt-2">لا توجد درجات مسجلة</p>
                </div>
            @endif
        </div>
    </div>

    {{-- إحصائيات الحضور --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">إحصائيات الحضور</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 text-center mb-3">
                    <div class="bg-light rounded p-3">
                        <h4 class="mb-0 text-primary">{{ $attendanceStats['total'] }}</h4>
                        <small class="text-muted">إجمالي الأيام</small>
                    </div>
                </div>
                <div class="col-md-2 text-center mb-3">
                    <div class="bg-light rounded p-3">
                        <h4 class="mb-0 text-success">{{ $attendanceStats['present'] }}</h4>
                        <small class="text-muted">حاضر</small>
                    </div>
                </div>
                <div class="col-md-2 text-center mb-3">
                    <div class="bg-light rounded p-3">
                        <h4 class="mb-0 text-danger">{{ $attendanceStats['absent'] }}</h4>
                        <small class="text-muted">غائب</small>
                    </div>
                </div>
                <div class="col-md-2 text-center mb-3">
                    <div class="bg-light rounded p-3">
                        <h4 class="mb-0 text-warning">{{ $attendanceStats['late'] }}</h4>
                        <small class="text-muted">متأخر</small>
                    </div>
                </div>
                <div class="col-md-2 text-center mb-3">
                    <div class="bg-light rounded p-3">
                        <h4 class="mb-0 text-info">{{ $attendanceStats['excused'] }}</h4>
                        <small class="text-muted">بعذر</small>
                    </div>
                </div>
                <div class="col-md-2 text-center mb-3">
                    <div class="bg-light rounded p-3">
                        <h4 class="mb-0 text-dark">{{ $attendanceStats['rate'] }}%</h4>
                        <small class="text-muted">نسبة الحضور</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
