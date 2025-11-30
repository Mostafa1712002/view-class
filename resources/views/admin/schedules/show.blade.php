@extends('layouts.app')

@section('title', 'عرض الجدول')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">جدول {{ $schedule->classRoom->name }} - {{ $schedule->classRoom->division }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.schedules.index') }}">الجداول الدراسية</a></li>
                        <li class="breadcrumb-item active">عرض</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.schedules.edit', $schedule) }}" class="btn btn-warning"><i data-feather="edit"></i> تعديل</a>
        <a href="{{ route('manage.schedules.index') }}" class="btn btn-secondary"><i data-feather="arrow-right"></i> رجوع</a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">معلومات الجدول</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>الفصل:</strong> {{ $schedule->classRoom->name }} - {{ $schedule->classRoom->division }}</div>
                <div class="col-md-3"><strong>المرحلة:</strong> {{ $schedule->classRoom->section->name ?? '-' }}</div>
                <div class="col-md-3"><strong>السنة الدراسية:</strong> {{ $schedule->academicYear->name }}</div>
                <div class="col-md-3"><strong>الفصل الدراسي:</strong> {{ $schedule->semester_label }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">الجدول الأسبوعي</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">الحصة</th>
                            @foreach($days as $dayNum => $dayName)
                                @if($dayNum != 5)
                                <th>{{ $dayName }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for($period = 1; $period <= $periodsCount; $period++)
                        <tr>
                            <td class="table-light"><strong>{{ $period }}</strong></td>
                            @foreach($days as $dayNum => $dayName)
                                @if($dayNum != 5)
                                <td>
                                    @if($timetable[$dayNum][$period])
                                        <div class="text-primary fw-bold">{{ $timetable[$dayNum][$period]->subject->name }}</div>
                                        <small class="text-muted">{{ $timetable[$dayNum][$period]->teacher->name }}</small>
                                        @if($timetable[$dayNum][$period]->room)
                                            <br><small class="badge bg-light-secondary">{{ $timetable[$dayNum][$period]->room }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @endif
                            @endforeach
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
