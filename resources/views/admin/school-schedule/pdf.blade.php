@php
    $pdf_title = trans('sprint4.school_schedule.pdf_title');
    $pdf_school = '';
    $pdf_date  = now()->format('Y-m-d');
    $days      = \App\Models\ScheduleEntry::DAYS_AR;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $pdf_title }}</title>
    @include('partials.pdf.styles')
    <style>
        /* Schedule-specific size overrides for landscape A4 */
        table.pdf-table thead th { font-size: 7.5px; padding: 4px 3px; }
        table.pdf-table tbody td { font-size: 7.5px; padding: 3px; }
        .entry { margin-bottom: 3px; padding: 2px 3px; background: #f5f5f5; border-radius:2px; }
        .entry strong { display: block; font-size:7px; }
        .entry .meta  { color: #555; font-size: 6.5px; }
    </style>
</head>
<body>

@include('partials.pdf.header')

<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:70px; text-align:center;">الحصة / الوقت</th>
            @foreach($days as $name)<th>{{ $name }}</th>@endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($slots as $slot)
        <tr>
            <td style="text-align:center; background:#f8f9fa;">
                <strong>{{ $slot->period_no }}</strong><br>
                <span style="font-size:6.5px; font-family:dejavusans; color:#555;">
                    {{ \Illuminate\Support\Str::limit($slot->starts_at, 5, '') }}–{{ \Illuminate\Support\Str::limit($slot->ends_at, 5, '') }}
                </span>
            </td>
            @foreach($days as $dayIdx => $dayName)
            @php $cellEntries = $entries->get($dayIdx . '-' . $slot->id, collect()); @endphp
            <td>
                @foreach($cellEntries as $e)
                <div class="entry">
                    <strong>{{ $e->classPeriod->subject->name ?? '—' }}</strong>
                    <span class="meta">{{ $e->classPeriod->teacher->name ?? '—' }} · {{ $e->classPeriod->classRoom->name ?? '—' }}</span>
                </div>
                @endforeach
            </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

@include('partials.pdf.footer')

</body>
</html>
