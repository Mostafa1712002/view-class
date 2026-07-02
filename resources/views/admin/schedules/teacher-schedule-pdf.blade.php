<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: xbriyaz, sans-serif; color: #1f2937; }
        h2 { text-align: center; margin: 0 0 4px; font-size: 16px; }
        .sub { text-align: center; color: #6b7280; font-size: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: center; vertical-align: middle; font-size: 10px; }
        th { background: #f1f5f9; }
        .period { background: #f8fafc; font-weight: bold; width: 60px; }
        .subject { color: #b45309; font-weight: bold; }
        .cls { color: #6b7280; font-size: 9px; }
        .room { color: #64748b; font-size: 8px; }
        .empty { color: #cbd5e1; }
    </style>
</head>
<body>
    <h2>الجدول الأسبوعي — {{ $teacher->name }}</h2>
    <div class="sub">{{ config('app.name') }}</div>
    <table>
        <thead>
            <tr>
                <th class="period">الحصة</th>
                @foreach($days as $dayNum => $dayName)
                    <th>{{ $dayName }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for($period = 1; $period <= $periodsCount; $period++)
            <tr>
                <td class="period">{{ $period }}</td>
                @foreach($days as $dayNum => $dayName)
                    <td>
                        @php $cell = $timetable[$dayNum][$period] ?? []; @endphp
                        @if(empty($cell))
                            <span class="empty">-</span>
                        @else
                            @foreach($cell as $p)
                                <div class="subject">{{ optional($p->subject)->name }}</div>
                                <div class="cls">{{ optional($p->schedule->classRoom)->name }}{{ optional($p->schedule->classRoom)->division ? ' - ' . $p->schedule->classRoom->division : '' }}</div>
                                @if($p->room)<div class="room">{{ $p->room }}</div>@endif
                            @endforeach
                        @endif
                    </td>
                @endforeach
            </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
