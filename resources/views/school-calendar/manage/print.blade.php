<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $pdf_title }}</title>
    @include('partials.pdf.styles')
    <style>
        .range-label { font-size: 12px; font-weight: bold; margin: 0 0 8px; color: #14233A; }
        table.pdf-table thead th { font-size: 9px; padding: 5px 4px; background: #14233A; color: #fff; }
        table.pdf-table tbody td { font-size: 9px; padding: 5px 4px; vertical-align: top; }
        .type-pill { display: inline-block; padding: 1px 6px; border-radius: 8px; color: #fff; font-size: 8px; }
        .ev-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; }
        .empty { text-align: center; color: #94a3b8; padding: 20px; font-size: 11px; }
    </style>
</head>
<body>

@include('partials.pdf.header')

<div class="range-label">@lang('school_calendar.print_range'): {{ $rangeLabel }}</div>

@if($events->isEmpty())
    <div class="empty">@lang('school_calendar.no_events')</div>
@else
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:18%;">@lang('school_calendar.field_type')</th>
            <th style="width:30%;">@lang('school_calendar.field_title')</th>
            <th style="width:14%;">@lang('school_calendar.field_start_date')</th>
            <th style="width:14%;">@lang('school_calendar.field_end_date')</th>
            <th style="width:24%;">@lang('school_calendar.field_location')</th>
        </tr>
    </thead>
    <tbody>
        @foreach($events as $event)
        <tr>
            <td>
                <span class="type-pill" style="background:{{ $event->eventTypeColor() }}">{{ $event->eventTypeLabel() }}</span>
            </td>
            <td>{{ $event->title }}</td>
            <td>
                {{ $event->start_date->format('Y-m-d') }}
                @if(! $event->all_day && $event->start_time) <br><span style="color:#64748b;font-size:8px;font-family:dejavusans;">{{ \Illuminate\Support\Str::limit($event->start_time, 5, '') }}</span>@endif
            </td>
            <td>
                {{ $event->end_date ? $event->end_date->format('Y-m-d') : '—' }}
                @if(! $event->all_day && $event->end_time) <br><span style="color:#64748b;font-size:8px;font-family:dejavusans;">{{ \Illuminate\Support\Str::limit($event->end_time, 5, '') }}</span>@endif
            </td>
            <td>{{ $event->location ?: '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
