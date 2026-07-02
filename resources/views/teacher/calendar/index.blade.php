@extends('layouts.app')

@section('title', __('school_calendar.teacher_title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('app-assets/vendors/css/calendars/fullcalendar.min.css') }}">
<style>
    #teacher-calendar { max-width: 100%; background: #fff; padding: 1rem; border-radius: .5rem; }
    .fc-event { cursor: pointer; }
    .cal-legend { display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 1rem; }
    .cal-legend .item { display: flex; align-items: center; font-size: .85rem; }
    .cal-legend .dot { width: 12px; height: 12px; border-radius: 3px; margin-{{ $isRtl ? 'left' : 'right' }}: .35rem; display: inline-block; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('school_calendar.teacher_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('school_calendar.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('school_calendar.teacher_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="ds-card card">
    <div class="ds-card-header card-header d-flex justify-content-between align-items-center">
        <h5 class="ds-card-title mb-0"><x-svg-icon name="calendar3" :size="16" /> @lang('school_calendar.teacher_title')</h5>
        @if(auth()->user()->canDo('calendar.create_event'))
            <a href="{{ route('manage.school-calendar.create') }}" class="btn btn-sm btn-primary">
                + @lang('school_calendar.btn_add')
            </a>
        @endif
    </div>
    <div class="card-body">
        <div class="cal-legend">
            <span class="item"><span class="dot" style="background:#10b981"></span>@lang('school_calendar.legend_lesson')</span>
            <span class="item"><span class="dot" style="background:#ef4444"></span>@lang('school_calendar.legend_exam')</span>
            <span class="item"><span class="dot" style="background:#f59e0b"></span>@lang('school_calendar.legend_assignment')</span>
            <span class="item"><span class="dot" style="background:#8b5cf6"></span>@lang('school_calendar.legend_virtual_class')</span>
            <span class="item"><span class="dot" style="background:#0ea5e9"></span>@lang('school_calendar.legend_appointment')</span>
            <span class="item"><span class="dot" style="background:#c9a04b"></span>@lang('school_calendar.legend_school_event')</span>
        </div>
        <div id="teacher-calendar"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('app-assets/vendors/js/extensions/moment.min.js') }}"></script>
<script src="{{ asset('app-assets/vendors/js/extensions/fullcalendar.min.js') }}"></script>
<script>
$(document).ready(function () {
    var isRtl = {{ $isRtl ? 'true' : 'false' }};

    $('#teacher-calendar').fullCalendar({
        header: {
            left:   isRtl ? 'next,prev today' : 'prev,next today',
            center: 'title',
            right:  'month,agendaWeek,agendaDay'
        },
        locale:     isRtl ? 'ar' : 'en',
        isRTL:      isRtl,
        editable:   false,
        eventLimit: true,
        timeFormat: 'HH:mm',
        events: {
            url: '{{ route('teacher.calendar.events') }}',
            error: function () {
                console.error('Failed to load teacher calendar events');
            }
        }
        // Events carry a `url`; FullCalendar navigates to it on click.
    });
});
</script>
@endpush
