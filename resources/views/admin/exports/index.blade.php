@extends('layouts.admin')

@section('title', 'تصدير البيانات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تصدير البيانات</h1>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="la la-user-graduate me-1"></i>تصدير الطلاب</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">تصدير قائمة جميع الطلاب مع بياناتهم الأساسية</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.exports.students', ['format' => 'csv']) }}" class="btn btn-success">
                            <i class="la la-file-csv me-1"></i>تصدير CSV
                        </a>
                        <a href="{{ route('admin.exports.students', ['format' => 'pdf']) }}" class="btn btn-danger">
                            <i class="la la-file-pdf me-1"></i>تصدير PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="la la-chalkboard-teacher me-1"></i>تصدير المعلمين</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">تصدير قائمة جميع المعلمين والمواد التي يدرسونها</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.exports.teachers', ['format' => 'csv']) }}" class="btn btn-success">
                            <i class="la la-file-csv me-1"></i>تصدير CSV
                        </a>
                        <a href="{{ route('admin.exports.teachers', ['format' => 'pdf']) }}" class="btn btn-danger">
                            <i class="la la-file-pdf me-1"></i>تصدير PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="la la-graduation-cap me-1"></i>تصدير الدرجات</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">تصدير سجل الدرجات للطلاب</p>
                    <form action="{{ route('admin.exports.grades') }}" method="GET">
                        <div class="mb-3">
                            <select name="class_id" class="form-select form-select-sm">
                                <option value="">كل الصفوف</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="format" value="csv" class="btn btn-success">
                                <i class="la la-file-csv me-1"></i>تصدير CSV
                            </button>
                            <button type="submit" name="format" value="pdf" class="btn btn-danger">
                                <i class="la la-file-pdf me-1"></i>تصدير PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="la la-check-square me-1"></i>تصدير الحضور</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">تصدير سجل الحضور والغياب</p>
                    <form action="{{ route('admin.exports.attendance') }}" method="GET">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الصف</label>
                                <select name="class_id" class="form-select form-select-sm">
                                    <option value="">كل الصفوف</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">من تاريخ</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">إلى تاريخ</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="format" value="csv" class="btn btn-success">
                                <i class="la la-file-csv me-1"></i>تصدير CSV
                            </button>
                            <button type="submit" name="format" value="pdf" class="btn btn-danger">
                                <i class="la la-file-pdf me-1"></i>تصدير PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
