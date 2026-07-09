@extends('layouts.app')

@section('title', __('school_calendar.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('app-assets/vendors/css/calendars/fullcalendar.min.css') }}">
<style>
    #school-calendar-view { max-width: 100%; background: #fff; padding: 1rem; border-radius: .5rem; }
    /* Only let the calendar grid scroll horizontally on phones; on desktop keep
       overflow visible so FullCalendar's "+more" popover isn't clipped. */
    @media (max-width: 767.98px) { #school-calendar-view { overflow-x: auto; } }
    .fc-event { cursor: default; }
    .event-detail-popup { display: none; position: absolute; z-index: 9999; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; max-width: 300px; box-shadow: 0 4px 20px rgba(0,0,0,.12); }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('school_calendar.title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('school_calendar.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('school_calendar.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="ds-card card">
    <div class="ds-card-header card-header">
        <h5 class="ds-card-title"><x-svg-icon name="calendar3" :size="16" /> @lang('school_calendar.title')</h5>
    </div>
    <div class="card-body">
        <div id="school-calendar-view"></div>
    </div>
</div>

{{-- Event detail tooltip --}}
<div id="event-popup" class="event-detail-popup">
    <strong id="popup-title"></strong>
    <div id="popup-type" class="text-muted small mb-1"></div>
    <div id="popup-date" class="small"></div>
    <div id="popup-location" class="small text-muted"></div>
    <div id="popup-desc" class="small mt-1"></div>
    <button type="button" id="popup-close" class="btn btn-sm btn-link p-0 mt-1">@lang('school_calendar.btn_close')</button>
</div>
@endsection

@push('scripts')
<script src="{{ asset('app-assets/vendors/js/extensions/moment.min.js') }}"></script>
<script src="{{ asset('app-assets/vendors/js/extensions/fullcalendar.min.js') }}"></script>
@if(app()->getLocale() === 'ar')
@endif
<script>
$(document).ready(function () {
    var isRtl = {{ $isRtl ? 'true' : 'false' }};

    $('#school-calendar-view').fullCalendar({
        header: {
            left:   isRtl ? 'next,prev today' : 'prev,next today',
            center: 'title',
            right:  'month,agendaWeek,agendaDay'
        },
        locale:     isRtl ? 'ar' : 'en',
        isRTL:      isRtl,
        editable:   false,
        eventLimit: true,
        events: {
            url: '{{ route('my.calendar.events.json') }}',
            error: function () {
                console.error('Failed to load calendar events');
            }
        },
        eventClick: function (event, jsEvent) {
            $('#popup-title').text(event.title);
            $('#popup-type').text(event.extendedProps ? event.extendedProps.type_label : '');
            $('#popup-date').text((event.start ? moment(event.start).format('YYYY-MM-DD') : '') + (event.end ? ' → ' + moment(event.end).format('YYYY-MM-DD') : ''));
            $('#popup-location').text(event.extendedProps && event.extendedProps.location ? event.extendedProps.location : '');
            $('#popup-desc').text(event.extendedProps && event.extendedProps.description ? event.extendedProps.description : '');

            var popup = $('#event-popup');
            popup.css({ top: jsEvent.pageY + 10, left: jsEvent.pageX + 10 }).show();
            jsEvent.stopPropagation();
        }
    });

    $(document).on('click', '#popup-close, body', function (e) {
        if (!$(e.target).closest('#event-popup').length || $(e.target).is('#popup-close')) {
            $('#event-popup').hide();
        }
    });
});
</script>
@endpush
