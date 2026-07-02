@extends('layouts.app')

@section('title', 'جدول المعلم')
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">جدول المعلم: {{ $teacher->name }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item active">جدول المعلم</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-body">
    @if($teachers)
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4 mb-1">
                    <label>اختر المعلم</label>
                    <select name="teacher_id" class="form-control" onchange="this.form.submit()">
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ $teacher->id == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">الجدول الأسبوعي</h4>
            <a href="{{ route('teacher.schedule.pdf', $teachers ? ['teacher_id' => $teacher->id] : []) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                <i class="la la-file-pdf-o"></i> تحميل PDF
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">الحصة</th>
                            @foreach($days as $dayNum => $dayName)
                                <th>{{ $dayName }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for($period = 1; $period <= $periodsCount; $period++)
                        <tr>
                            <td class="table-light"><strong>{{ $period }}</strong></td>
                            @foreach($days as $dayNum => $dayName)
                                <td>
                                    @php $cell = $timetable[$dayNum][$period] ?? []; @endphp
                                    @if(empty($cell))
                                        <span class="text-muted">-</span>
                                    @else
                                        @foreach($cell as $p)
                                            <div class="text-primary fw-bold">{{ optional($p->subject)->name }}</div>
                                            <small class="text-muted">{{ optional($p->schedule->classRoom)->name }}{{ optional($p->schedule->classRoom)->division ? ' - ' . $p->schedule->classRoom->division : '' }}</small>
                                            @if($p->room)
                                                <br><small class="badge bg-light-secondary">{{ $p->room }}</small>
                                            @endif
                                        @endforeach
                                    @endif
                                </td>
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
