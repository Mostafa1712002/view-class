<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة المعلمين</title>
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
            background-color: #17a2b8;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h1>قائمة المعلمين</h1>
    <p>تاريخ التصدير: {{ now()->format('Y/m/d H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('common.name')</th>
                <th>@lang('common.email')</th>
                <th>@lang('common.phone')</th>
                <th>المواد</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $index => $teacher)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $teacher->name }}</td>
                <td>{{ $teacher->email }}</td>
                <td>{{ $teacher->phone ?? '-' }}</td>
                <td>{{ $teacher->subjects->pluck('name')->implode(', ') ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        المنصة الذهبية - تم إنشاء هذا التقرير آلياً
    </div>
</body>
</html>
