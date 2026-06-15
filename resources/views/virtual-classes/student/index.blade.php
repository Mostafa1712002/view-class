@extends('layouts.app')

@section('title', __('virtual_classes.student_title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('virtual_classes.student_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">@lang('virtual_classes.breadcrumb_home')</a>
                </li>
                <li class="breadcrumb-item active">@lang('virtual_classes.student_title')</li>
            </ol>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-content">
        <div class="card-body p-0">
            @if($classes->isEmpty())
                <p class="text-center text-muted py-4">
                    <i class="la la-video la-2x d-block mb-2"></i>
                    @lang('virtual_classes.student_empty')
                </p>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>@lang('virtual_classes.field_title')</th>
                            <th>@lang('virtual_classes.field_teacher')</th>
                            <th>@lang('virtual_classes.field_scheduled_at')</th>
                            <th>@lang('virtual_classes.field_duration')</th>
                            <th>@lang('virtual_classes.field_status')</th>
                            <th>@lang('virtual_classes.field_join')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $vc)
                        <tr>
                            <td>{{ $vc->title }}</td>
                            <td>{{ optional($vc->teacher)->name }}</td>
                            <td>{{ $vc->scheduled_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $vc->duration_minutes }} @lang('virtual_classes.minutes')</td>
                            <td>
                                <span class="badge badge-{{ $vc->statusColor() }}">{{ $vc->statusLabel() }}</span>
                            </td>
                            <td>
                                {{-- Join button appears only inside the 5-min window. Posting
                                     through the join route records the student's entry time
                                     (the source for attendance) before redirecting out. --}}
                                @if($vc->isJoinable() && $vc->participantUrl())
                                    <form method="POST" action="{{ route('my.virtual-classes.join', $vc->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <x-svg-icon name="camera-video" size="14" /> @lang('virtual_classes.btn_join')
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small" title="@lang('virtual_classes.join_window_hint')">
                                        @lang('virtual_classes.join_not_yet')
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($classes->hasPages())
                <div class="p-2">{{ $classes->links() }}</div>
            @endif
            @endif
        </div>
    </div>
</div>
@endsection
