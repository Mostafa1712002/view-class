@extends('layouts.app')

@section('title', 'تفاصيل القسم')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">{{ $section->name }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.sections.index') }}">الأقسام</a></li>
                        <li class="breadcrumb-item active">التفاصيل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.sections.edit', $section) }}" class="btn btn-warning">
            <i data-feather="edit"></i> تعديل
        </a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">معلومات القسم</h4>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>الاسم</th>
                            <td>{{ $section->name }}</td>
                        </tr>
                        <tr>
                            <th>المدرسة</th>
                            <td>{{ $section->school->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>الجنس</th>
                            <td>{{ $section->gender_label }}</td>
                        </tr>
                        <tr>
                            <th>المرحلة</th>
                            <td>{{ $section->level_label }}</td>
                        </tr>
                        <tr>
                            <th>الوصف</th>
                            <td>{{ $section->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>الحالة</th>
                            <td>
                                @if($section->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">معطل</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الفصول ({{ $section->classes->count() }})</h4>
                </div>
                <div class="card-body">
                    @if($section->classes->count() > 0)
                        <ul class="list-group">
                            @foreach($section->classes as $class)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $class->name }} - {{ $class->division }}
                                    <span class="badge bg-primary">{{ $class->grade_level_label }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">لا توجد فصول</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
