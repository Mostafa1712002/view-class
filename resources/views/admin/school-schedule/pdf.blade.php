<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>@lang('sprint4.school_schedule.pdf_title')</title>
<style>
    body { font-family: 'DejaVu Sans', Arial, sans-serif; direction: rtl; font-size: 11px; }
    h1 { text-align: center; font-size: 16px; margin: 0 0 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #888; padding: 4px; vertical-align: top; }
    th { background: #eee; font-size: 12px; }
    .entry { margin-bottom: 4px; padding: 2px 4px; background: #f5f5f5; }
    .entry strong { display: block; }
    .entry .meta { color: #555; font-size: 10px; }
</style>
</head>
<body>
<h1>@lang('sprint4.school_schedule.pdf_title')</h1>

@php $days = \App\Models\ScheduleEntry::DAYS_AR; @endphp

<table>
    <thead>
        <tr>
            <th style="width: 80px">#</th>
            @foreach($days as $name)<th>{{ $name }}</th>@endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($slots as $slot)
            <tr>
                <td>
                    <strong>{{ $slot->period_no }}</strong><br>
                    <small>{{ \Illuminate\Support\Str::limit($slot->starts_at, 5, '') }}–{{ \Illuminate\Support\Str::limit($slot->ends_at, 5, '') }}</small>
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
</body>
</html>
