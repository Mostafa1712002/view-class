@extends('layouts.app')

@section('title', __('users.schedule_link').' — '.$student->name)
@section('body_class','theme-light')

@include('admin.users.students._sub_styles')

@section('content')
@php $active = 'schedule'; @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $student->name }} — @lang('users.schedule_link')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.show', $student->id) }}">{{ $student->name }}</a></li>
                <li class="breadcrumb-item active">@lang('users.schedule_link')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('admin.users.students._header')
    @include('admin.users.students._subnav')

    <div class="card">
        <div class="card-body">
            @if(!$class)
                <div class="empty-state">
                    <div class="icon-wrap"><i class="la la-chalkboard"></i></div>
                    <h4>@lang('users.student_no_class')</h4>
                    <p>@lang('users.student_no_schedule')</p>
                </div>
            @elseif($periods->isEmpty())
                <div class="empty-state">
                    <div class="icon-wrap"><i class="la la-calendar"></i></div>
                    <h4>@lang('users.student_no_schedule')</h4>
                    <p>{{ $class->name }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>@lang('users.student_day')</th>
                                <th>@lang('users.student_period')</th>
                                <th>@lang('users.student_subject')</th>
                                <th>@lang('users.student_teacher')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($periods as $day => $items)
                            @foreach($items as $p)
                                <tr>
                                    <td>{{ $days[$day] ?? $day }}</td>
                                    <td>{{ $p->period_number }}</td>
                                    <td>{{ optional($p->subject)->name ?? '—' }}</td>
                                    <td>{{ optional($p->teacher)->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
