@extends('layouts.app')

@section('title', __('users.absences_link').' — '.$student->name)
@section('body_class','theme-light')

@include('admin.users.students._sub_styles')

@section('content')
@php $active = 'attendance'; @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $student->name }} — @lang('users.absences_link')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.show', $student->id) }}">{{ $student->name }}</a></li>
                <li class="breadcrumb-item active">@lang('users.absences_link')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('admin.users.students._header')
    @include('admin.users.students._subnav')

    <div class="card">
        <div class="card-body">
            @if($attendances->isEmpty())
                <div class="empty-state">
                    <div class="icon-wrap"><i class="la la-times-circle"></i></div>
                    <h4>@lang('users.student_no_attendance')</h4>
                    <p>—</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>@lang('users.student_date')</th>
                                <th>@lang('users.class')</th>
                                <th>@lang('users.student_subject')</th>
                                <th>@lang('users.student_attendance_status')</th>
                                <th>@lang('users.student_notes_field')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($attendances as $a)
                            @php
                                $statusClass = match($a->status) {
                                    'present' => 'on',
                                    'absent' => 'off',
                                    'late', 'excused' => 'on',
                                    default => 'on',
                                };
                            @endphp
                            <tr>
                                <td>{{ $a->date instanceof \Carbon\Carbon ? $a->date->format('Y-m-d') : $a->date }}</td>
                                <td>{{ optional($a->classRoom)->name ?? '—' }}</td>
                                <td>{{ optional($a->subject)->name ?? '—' }}</td>
                                <td><span class="status-pill {{ $statusClass }}">@lang('users.student_'.$a->status)</span></td>
                                <td><small class="text-muted">{{ $a->notes ?? '—' }}</small></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">{{ $attendances->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
