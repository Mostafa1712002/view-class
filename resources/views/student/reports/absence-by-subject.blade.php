@extends('layouts.admin')

@section('title', 'الغياب حسب المادة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">الغياب حسب المادة</h1>
        <a href="{{ route('student.reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="la la-arrow-right"></i> العودة للتقارير
        </a>
    </div>

    @if($academicYear)
        <p class="text-muted mb-4">العام الدراسي: <strong>{{ $academicYear->name }}</strong></p>
    @endif

    @if($grouped->count() > 0)
        {{-- Summary overview --}}
        <div class="row mb-4">
            @foreach($grouped as $subject => $rows)
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <h3 class="text-danger mb-1">{{ $rows->count() }}</h3>
                        <small class="text-muted">{{ $subject }}</small>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Detailed table per subject --}}
        @foreach($grouped as $subject => $rows)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $subject }}</h5>
                <span class="badge bg-danger">{{ $rows->count() }} غياب</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>التاريخ</th>
                                <th>اليوم</th>
                                <th>الحصة</th>
                                <th class="text-center">الحالة</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $i => $attendance)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $attendance->date->format('Y-m-d') }}</td>
                                <td>{{ $attendance->date->translatedFormat('l') }}</td>
                                <td>{{ $attendance->period ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $attendance->status_color }}">
                                        {{ $attendance->status_label }}
                                    </span>
                                </td>
                                <td>{{ $attendance->notes ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="la la-book display-1 text-muted"></i>
                <p class="mt-3 text-muted">لا توجد سجلات غياب للعام الدراسي الحالي</p>
            </div>
        </div>
    @endif
</div>
@endsection
