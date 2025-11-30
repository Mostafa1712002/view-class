<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سجل الدرجات</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #ffc107;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h1>سجل الدرجات</h1>
    <p>تاريخ التصدير: {{ now()->format('Y/m/d H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الطالب</th>
                <th>المادة</th>
                <th>الاختبار</th>
                <th>الدرجة</th>
                <th>الدرجة القصوى</th>
                <th>النسبة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grades as $index => $grade)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $grade->student?->name ?? '-' }}</td>
                <td>{{ $grade->subject?->name ?? '-' }}</td>
                <td>{{ $grade->exam?->title ?? '-' }}</td>
                <td>{{ $grade->score }}</td>
                <td>{{ $grade->max_score }}</td>
                <td class="{{ $grade->percentage >= 60 ? 'pass' : 'fail' }}">
                    {{ number_format($grade->percentage, 1) }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        المنصة الذهبية - تم إنشاء هذا التقرير آلياً
    </div>
</body>
</html>
