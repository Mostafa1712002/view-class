@extends('layouts.admin')

@section('title', 'أيام الغياب')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">أيام الغياب</h1>
        <a href="{{ route('student.reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="la la-arrow-right"></i> العودة للتقارير
        </a>
    </div>

    {{-- Date filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">تصفية</button>
                    <a href="{{ route('student.reports.absence-days') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">سجل أيام الغياب</h5>
            <span class="badge bg-danger fs-6">{{ $absences->count() }} سجل</span>
        </div>
        <div class="card-body">
            @if($absences->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>التاريخ</th>
                                <th>اليوم</th>
                                <th>المادة</th>
                                <th>الحصة</th>
                                <th class="text-center">الحالة</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($absences as $i => $attendance)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $attendance->date->format('Y-m-d') }}</td>
                                <td>{{ $attendance->date->translatedFormat('l') }}</td>
                                <td>{{ $attendance->subject?->name ?? '-' }}</td>
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
            @else
                <div class="text-center py-5">
                    <i class="la la-calendar-check display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد سجلات غياب في الفترة المحددة</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
