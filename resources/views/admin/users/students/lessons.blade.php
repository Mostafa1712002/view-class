@extends('layouts.app')

@section('title', __('users.classes_link').' — '.$student->name)
@section('body_class','theme-light')

@include('admin.users.students._sub_styles')

@section('content')
@php $active = 'lessons'; @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $student->name }} — @lang('users.classes_link')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.show', $student->id) }}">{{ $student->name }}</a></li>
                <li class="breadcrumb-item active">@lang('users.classes_link')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('admin.users.students._header')
    @include('admin.users.students._subnav')

    <div class="card">
        <div class="card-body">
            @if($classes->isEmpty())
                <div class="empty-state">
                    <div class="icon-wrap"><i class="la la-chalkboard"></i></div>
                    <h4>@lang('users.student_no_lessons')</h4>
                    <p>@lang('users.student_no_class')</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>@lang('users.class')</th>
                                <th>@lang('users.grade_level')</th>
                                <th>@lang('users.student_subject')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($classes as $c)
                            <tr>
                                <td><strong>{{ $c->name }}</strong></td>
                                <td>{{ optional($c->section)->name ?? '—' }}</td>
                                <td>
                                    @forelse($c->subjects as $s)
                                        <span class="grade-chip me-1">{{ $s->name }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
