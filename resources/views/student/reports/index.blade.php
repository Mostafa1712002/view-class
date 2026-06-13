@extends('layouts.admin')

@section('title', 'تقارير الغياب')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تقارير الغياب</h1>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column align-items-center text-center py-4">
                    <i class="la la-calendar-times display-4 text-danger mb-3"></i>
                    <h5 class="card-title">أيام الغياب</h5>
                    <p class="card-text text-muted">سجل تفصيلي بأيام الغياب المعذورة وغير المعذورة مع تصفية بالتاريخ.</p>
                    <a href="{{ route('student.reports.absence-days') }}" class="btn btn-outline-danger mt-auto">عرض التقرير</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column align-items-center text-center py-4">
                    <i class="la la-chart-bar display-4 text-primary mb-3"></i>
                    <h5 class="card-title">ملخص الحضور والغياب</h5>
                    <p class="card-text text-muted">إجماليات الحضور والغياب والتأخر ونسبة الحضور للعام الدراسي الحالي.</p>
                    <a href="{{ route('student.reports.absence-summary') }}" class="btn btn-outline-primary mt-auto">عرض التقرير</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column align-items-center text-center py-4">
                    <i class="la la-book display-4 text-warning mb-3"></i>
                    <h5 class="card-title">الغياب حسب المادة</h5>
                    <p class="card-text text-muted">توزيع الغياب على المواد الدراسية خلال العام الحالي.</p>
                    <a href="{{ route('student.reports.absence-by-subject') }}" class="btn btn-outline-warning mt-auto">عرض التقرير</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
