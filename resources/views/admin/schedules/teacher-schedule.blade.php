@extends('layouts.app')

@section('title', 'جدول المعلم')

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
        <div class="card-header">
            <h4 class="card-title">الجدول الأسبوعي</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">الحصة</th>
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
                                        @php $p = $timetable[$dayNum][$period]; @endphp
                                        <div class="text-primary fw-bold">{{ $p->subject->name }}</div>
                                        <small class="text-muted">{{ $p->schedule->classRoom->name }} - {{ $p->schedule->classRoom->division }}</small>
                                        @if($p->room)
                                            <br><small class="badge bg-light-secondary">{{ $p->room }}</small>
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
