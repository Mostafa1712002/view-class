<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سجل الحضور</title>
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
            background-color: #28a745;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .present { color: green; }
        .absent { color: red; }
        .late { color: orange; }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h1>سجل الحضور والغياب</h1>
    <p>الفترة: من {{ $dateFrom }} إلى {{ $dateTo }}</p>
    <p>تاريخ التصدير: {{ now()->format('Y/m/d H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>الطالب</th>
                <th>الصف</th>
                <th>الحالة</th>
                <th>ملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendance as $index => $record)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $record->date->format('Y/m/d') }}</td>
                <td>{{ $record->student?->name ?? '-' }}</td>
                <td>{{ $record->student?->classRoom?->name ?? '-' }}</td>
                <td class="{{ $record->status }}">
                    {{ match($record->status) {
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'excused' => 'معذور',
                        default => $record->status,
                    } }}
                </td>
                <td>{{ $record->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        المنصة الذهبية - تم إنشاء هذا التقرير آلياً
    </div>
</body>
</html>
