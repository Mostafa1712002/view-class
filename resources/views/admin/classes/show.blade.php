@extends('layouts.app')

@section('title', 'تفاصيل الفصل')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">{{ $class->name }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.classes.index') }}">الفصول</a></li>
                        <li class="breadcrumb-item active">التفاصيل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.classes.edit', $class) }}" class="btn btn-warning"><i data-feather="edit"></i> تعديل</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">معلومات الفصل</h4></div>
                <div class="card-body">
                    <table class="table">
                        <tr><th>الاسم</th><td>{{ $class->name }}</td></tr>
                        <tr><th>القسم</th><td>{{ $class->section->name ?? '-' }}</td></tr>
                        <tr><th>السنة الدراسية</th><td>{{ $class->academicYear->name ?? '-' }}</td></tr>
                        <tr><th>الصف</th><td>{{ $class->grade_level_label }}</td></tr>
                        <tr><th>الشعبة</th><td>{{ $class->division }}</td></tr>
                        <tr><th>السعة</th><td>{{ $class->capacity }}</td></tr>
                        <tr><th>الغرفة</th><td>{{ $class->room ?? '-' }}</td></tr>
                        <tr><th>الحالة</th><td>@if($class->is_active)<span class="badge bg-success">نشط</span>@else<span class="badge bg-secondary">معطل</span>@endif</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">الطلاب ({{ $class->students->count() }})</h4></div>
                <div class="card-body">
                    @if($class->students->count() > 0)
                        <ul class="list-group">
                            @foreach($class->students as $student)
                                <li class="list-group-item">{{ $student->name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">لا يوجد طلاب</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
