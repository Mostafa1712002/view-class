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


<div class="ds-card card">
    <div class="ds-card-header card-header">
        <h5 class="ds-card-title"><x-svg-icon name="camera-video" :size="16" /> @lang('virtual_classes.student_title')</h5>
    </div>
    @if($classes->isEmpty())
        <div class="ds-empty">
            <div class="ds-empty-icon"><x-svg-icon name="camera-video-off" :size="36" /></div>
            <div class="ds-empty-title">@lang('virtual_classes.student_empty')</div>
            <div class="ds-empty-desc">@lang('virtual_classes.student_empty')</div>
        </div>
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
                    <td class="text-nowrap" dir="ltr">{{ $vc->scheduled_at->format('Y-m-d H:i') }}</td>
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
                                    <x-svg-icon name="camera-video" :size="14" /> @lang('virtual_classes.btn_join')
                                </button>
                            </form>
                        @elseif($vc->hasEnded())
                            <span class="text-muted small">@lang('virtual_classes.join_ended')</span>
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
        <div class="ds-card-footer card-footer">{{ $classes->links() }}</div>
    @endif
    @endif
</div>
@endsection
