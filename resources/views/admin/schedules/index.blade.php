@extends('layouts.app')

@section('title', 'الجداول الدراسية')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">الجداول الدراسية</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active">الجداول الدراسية</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.schedules.create') }}" class="btn btn-primary"><i data-feather="plus"></i> إضافة جدول</a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">فلترة</h4>
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3 mb-1">
                    <label>الفصل</label>
                    <select name="class_id" class="form-control">
                        <option value="">الكل</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }} - {{ $class->division }} ({{ $class->section->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-1">
                    <label>السنة الدراسية</label>
                    <select name="academic_year_id" class="form-control">
                        <option value="">الكل</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-1">
                    <label>الفصل الدراسي</label>
                    <select name="semester" class="form-control">
                        <option value="">الكل</option>
                        <option value="first" {{ request('semester') == 'first' ? 'selected' : '' }}>الفصل الأول</option>
                        <option value="second" {{ request('semester') == 'second' ? 'selected' : '' }}>الفصل الثاني</option>
                    </select>
                </div>
                <div class="col-md-3 mb-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary ml-1">بحث</button>
                    <a href="{{ route('manage.schedules.index') }}" class="btn btn-outline-secondary">إعادة</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الفصل</th>
                        <th>المرحلة</th>
                        <th>السنة الدراسية</th>
                        <th>الفصل الدراسي</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->id }}</td>
                        <td>{{ $schedule->classRoom->name }} - {{ $schedule->classRoom->division }}</td>
                        <td>{{ $schedule->classRoom->section->name ?? '-' }}</td>
                        <td>{{ $schedule->academicYear->name }}</td>
                        <td>{{ $schedule->semester_label }}</td>
                        <td>
                            @if($schedule->is_active)
                                <span class="badge bg-success">نشط</span>
                            @else
                                <span class="badge bg-secondary">غير نشط</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('manage.schedules.show', $schedule) }}" class="btn btn-sm btn-info" title="عرض"><i data-feather="eye"></i></a>
                            <a href="{{ route('manage.schedules.edit', $schedule) }}" class="btn btn-sm btn-warning" title="تعديل"><i data-feather="edit"></i></a>
                            <form action="{{ route('manage.schedules.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="حذف"><i data-feather="trash-2"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">لا توجد جداول</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $schedules->links() }}
        </div>
    </div>
</div>
@endsection
