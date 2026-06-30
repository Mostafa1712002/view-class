@extends('layouts.app')

@section('title', __('school_calendar.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('app-assets/vendors/css/calendars/fullcalendar.min.css') }}">
<style>
    #school-calendar { max-width: 100%; background: #fff; padding: 1rem; border-radius: .5rem; }
    .fc-event { cursor: pointer; }
    .fc-event-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-inline-end: 5px; }
    /* #233: the theme clips `html body .content.app-content { overflow:hidden }`,
       so a BS4/Popper absolute dropdown gets cut off under the card. The print
       menu below is positioned `fixed` by JS, which escapes the ancestor clip. */
    #cal-print-menu.show { display: block; }
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
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        @if(auth()->user()->canDo('calendar.print'))
        <div class="btn-group">
            <button type="button" id="cal-print-btn" class="btn btn-outline-secondary dropdown-toggle"
                    aria-haspopup="true" aria-expanded="false">
                <x-svg-icon name="printer" :size="16" /> @lang('school_calendar.btn_print')
            </button>
            <div class="dropdown-menu" id="cal-print-menu">
                <a class="dropdown-item" href="{{ route('manage.school-calendar.print', ['view' => 'day']) }}" target="_blank">@lang('school_calendar.print_day')</a>
                <a class="dropdown-item" href="{{ route('manage.school-calendar.print', ['view' => 'week']) }}" target="_blank">@lang('school_calendar.print_week')</a>
                <a class="dropdown-item" href="{{ route('manage.school-calendar.print', ['view' => 'month']) }}" target="_blank">@lang('school_calendar.print_month')</a>
            </div>
        </div>
        @endif
        @if(auth()->user()->canDo('calendar.create_event'))
        <a href="{{ route('manage.school-calendar.create') }}" class="btn btn-primary">
            <x-svg-icon name="plus-lg" :size="16" /> @lang('school_calendar.btn_add')
        </a>
        @endif
    </div>
</div>


<div class="ds-card card">
    <div class="ds-card-header card-header">
        <h5 class="ds-card-title"><x-svg-icon name="calendar3" :size="16" /> @lang('school_calendar.title')</h5>
    </div>
    <div class="card-body">
        <div id="school-calendar"></div>
    </div>
</div>

{{-- Upcoming events list --}}
<div class="ds-card card mt-2">
    <div class="ds-card-header card-header">
        <h5 class="ds-card-title"><x-svg-icon name="calendar-event" :size="16" /> @lang('school_calendar.upcoming_events')</h5>
    </div>
    <div class="card-body p-0">
        @if($upcoming->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="calendar-x" :size="32" /></div>
                <div class="ds-empty-title">@lang('school_calendar.no_events')</div>
                <div class="ds-empty-desc">@lang('school_calendar.no_events')</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>@lang('school_calendar.field_title')</th>
                        <th>@lang('school_calendar.field_type')</th>
                        <th>@lang('school_calendar.field_start_date')</th>
                        <th>@lang('school_calendar.field_end_date')</th>
                        <th>@lang('school_calendar.field_audience')</th>
                        <th>@lang('school_calendar.field_actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upcoming as $event)
                    <tr>
                        <td>
                            <span class="fc-event-dot" style="background:{{ $event->eventTypeColor() }}"></span>
                            {{ $event->title }}
                        </td>
                        <td><span class="ds-badge-info badge">{{ $event->eventTypeLabel() }}</span></td>
                        <td dir="ltr" class="text-start">{{ $event->start_date->format('Y-m-d') }}</td>
                        <td dir="ltr" class="text-start">{{ $event->end_date ? $event->end_date->format('Y-m-d') : '—' }}</td>
                        <td>
                            @if($event->audience)
                                @foreach($event->audience as $aud)
                                    <span class="badge badge-light">@lang('school_calendar.audience_' . $aud)</span>
                                @endforeach
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if(auth()->user()->canDo('calendar.edit_event'))
                            <a href="{{ route('manage.school-calendar.edit', $event->id) }}"
                               class="ds-action-btn" title="تعديل" aria-label="تعديل">
                                <x-svg-icon name="pencil" :size="15" />
                            </a>
                            @endif
                            @if(auth()->user()->canDo('calendar.delete_event'))
                            <form action="{{ route('manage.school-calendar.destroy', $event->id) }}" method="POST" class="d-inline" id="del-form-{{ $event->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="ds-action-btn text-danger btn-delete"
                                        data-id="{{ $event->id }}" data-title="{{ $event->title }}"
                                        title="@lang('school_calendar.btn_delete')" aria-label="@lang('school_calendar.btn_delete')">
                                    <x-svg-icon name="trash" :size="15" />
                                </button>
                            </form>
                            @endif
                            @if(! auth()->user()->canDo('calendar.edit_event') && ! auth()->user()->canDo('calendar.delete_event'))
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
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

    // #233: the print menu was both clipped by the theme's
    // `.content.app-content{overflow:hidden}` AND buried under the calendar card's
    // stacking context. Relocate it to <body> on open and pin it under the button
    // with fixed positioning (same approach as the global .tw-floating-menu helper),
    // which escapes the clip and the stacking context in one move.
    // ponytail: doesn't track scroll; closes on outside click — fine for 3 links.
    var $printBtn = $('#cal-print-btn'), $printMenu = $('#cal-print-menu');
    if ($printBtn.length) {
        $printBtn.on('click', function (e) {
            e.stopPropagation();
            var show = !$printMenu.hasClass('show');
            if (show) {
                document.body.appendChild($printMenu[0]);
                $printMenu.addClass('show');
                // width:max-content so the fixed menu hugs its content (auto stretches
                // to ~viewport when it's a body child), then right-align under the button.
                // right:auto — otherwise BS4's RTL .dropdown-menu keeps a `right` value
                // that wins over `left` (both set + fixed width → RTL honors `right`).
                $printMenu.css({ position: 'fixed', 'z-index': 1060, width: 'max-content', maxWidth: '90vw', right: 'auto' });
                var r = this.getBoundingClientRect();
                $printMenu.css({ top: r.bottom + 'px', left: Math.max(8, r.right - $printMenu.outerWidth()) + 'px' });
            } else {
                $printMenu.removeClass('show');
            }
            $printBtn.attr('aria-expanded', show ? 'true' : 'false');
        });
        $(document).on('click', function () {
            $printMenu.removeClass('show');
            $printBtn.attr('aria-expanded', 'false');
        });
        $printMenu.on('click', function (e) { e.stopPropagation(); });
    }

    $('#school-calendar').fullCalendar({
        header: {
            left:   isRtl ? 'next,prev today' : 'prev,next today',
            center: 'title',
            right:  'month,agendaWeek,agendaDay'
        },
        locale:   isRtl ? 'ar' : 'en',
        isRTL:    isRtl,
        editable: false,
        eventLimit: true,
        events: {
            url: '{{ route('manage.school-calendar.events.json') }}',
            error: function () {
                console.error('Failed to load calendar events');
            }
        },
        eventRender: function (event, element) {
            if (event.extendedProps && event.extendedProps.location) {
                element.find('.fc-title').after(
                    $('<div class="fc-location" style="font-size:.75em;opacity:.8"></div>')
                        .text(event.extendedProps.location)
                );
            }
        },
        eventClick: function (event) {
            if (event.extendedProps && event.extendedProps.edit_url) {
                window.location.href = event.extendedProps.edit_url;
            }
        }
    });

    // Delete confirmation
    $(document).on('click', '.btn-delete', function () {
        var id    = $(this).data('id');
        var title = $(this).data('title');
        var msg   = '@lang('school_calendar.confirm_delete')'.replace(':title', title);

        window.vcConfirm({ title: msg }).then(function (r) {
            if (r.isConfirmed) {
                document.getElementById('del-form-' + id).submit();
            }
        });
    });
});
</script>
@endpush
