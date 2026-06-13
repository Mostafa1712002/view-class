@extends('layouts.admin')

@section('title', 'ملف الإنجاز')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">ملف الإنجاز</h1>
    </div>

    @if($academicYear)
        <p class="text-muted mb-4">العام الدراسي: <strong>{{ $academicYear->name }}</strong></p>
    @endif

    {{-- Summary stat cards --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-primary text-white text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $grades->count() }}</h2>
                    <small>المواد الدراسية</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card {{ $avgGrade !== null && $avgGrade >= 60 ? 'bg-success' : 'bg-secondary' }} text-white text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $avgGrade ?? '-' }}{{ $avgGrade !== null ? '%' : '' }}</h2>
                    <small>المعدل العام</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card {{ $attendanceRate >= 75 ? 'bg-success' : 'bg-danger' }} text-white text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $attendanceRate }}%</h2>
                    <small>نسبة الحضور</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-warning text-dark text-center">
                <div class="card-body py-3">
                    <h2 class="mb-0">{{ $certificates->count() }}</h2>
                    <small>الشهادات والتكريمات</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Top grades --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">أعلى الدرجات</h5></div>
                <div class="card-body">
                    @if($topGrades->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>المادة</th>
                                    <th class="text-center">المجموع</th>
                                    <th class="text-center">التقدير</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topGrades as $grade)
                                <tr>
                                    <td>{{ $grade->subject?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $grade->total >= 60 ? 'success' : 'danger' }}">
                                            {{ number_format($grade->total, 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        {{ $grade->letter_grade ?? '-' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="la la-award display-4 text-muted"></i>
                        <p class="text-muted mt-2">لا توجد درجات منشورة بعد</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent exam results --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">نتائج الاختبارات الأخيرة</h5></div>
                <div class="card-body">
                    @if($examResults->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>الاختبار</th>
                                    <th>المادة</th>
                                    <th class="text-center">الدرجة</th>
                                    <th class="text-center">النتيجة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($examResults as $result)
                                <tr>
                                    <td>{{ $result->exam?->title ?? '-' }}</td>
                                    <td>{{ $result->exam?->subject?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        {{ $result->score }}
                                        @if($result->percentage !== null)
                                            <span class="text-muted">({{ round($result->percentage, 1) }}%)</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $result->status === 'graded' ? ($result->score >= ($result->exam?->pass_marks ?? 0) ? 'success' : 'danger') : 'secondary' }}">
                                            {{ \App\Models\StudentExam::STATUSES[$result->status] ?? $result->status }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="la la-file-alt display-4 text-muted"></i>
                        <p class="text-muted mt-2">لا توجد نتائج اختبارات بعد</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Certificates --}}
    @if($certificates->count() > 0)
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">الشهادات والتكريمات</h5></div>
        <div class="card-body">
            <div class="row">
                @foreach($certificates as $cert)
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card border">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="la la-certificate text-warning fs-3 me-2"></i>
                                <h6 class="mb-0">{{ $cert->title }}</h6>
                            </div>
                            @if($cert->issue_date)
                            <p class="text-muted small mb-1">
                                <i class="la la-calendar me-1"></i>{{ $cert->issue_date->format('Y-m-d') }}
                            </p>
                            @endif
                            @if($cert->note)
                            <p class="text-muted small mb-0">{{ $cert->note }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
