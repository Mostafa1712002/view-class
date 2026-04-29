<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>الخطة الأسبوعية — {{ $weekStart->format('Y-m-d') }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 11px; color: #333; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #9c6b1f; text-align: center; }
        .meta { text-align: center; color: #666; margin-bottom: 10px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d4b16a; padding: 5px; vertical-align: top; }
        th { background: #f8e8c1; color: #6b4711; font-weight: bold; text-align: center; }
        td { background: #fff; }
        .status { text-align: center; width: 30px; }
        .status.green { background: #d8f5d3; color: #1f6f4a; }
        .status.yellow { background: #fff5d3; color: #b78420; }
        .empty { text-align: center; color: #999; font-style: italic; padding: 20px; }
    </style>
</head>
<body>
    <h1>الخطة الأسبوعية</h1>
    <div class="meta">
        من {{ $weekStart->format('Y-m-d') }} إلى {{ $weekEnd->format('Y-m-d') }}
        &nbsp;•&nbsp;
        طُبع في {{ now()->format('Y-m-d H:i') }}
    </div>

    @if($weekPlans->isEmpty())
        <div class="empty">لا توجد خطط للأسبوع المحدد.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:30px;">الحالة</th>
                    <th>المعلم</th>
                    <th>المادة</th>
                    <th>الفصل</th>
                    <th>الأهداف</th>
                    <th>الواجبات والمهام</th>
                    <th>الملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($weekPlans as $plan)
                    <tr>
                        <td class="status {{ $plan->is_prepared ? 'green' : 'yellow' }}">
                            {{ $plan->is_prepared ? '✓' : '○' }}
                        </td>
                        <td>{{ $plan->teacher?->name }}</td>
                        <td>{{ $plan->subject?->name }}</td>
                        <td>{{ $plan->classRoom?->name }}</td>
                        <td>{{ $plan->objectives }}</td>
                        <td>{{ $plan->homework }}</td>
                        <td>{{ $plan->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
