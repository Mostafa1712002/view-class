<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طلب القبول {{ $application->code }}</title>
    <style>
        body { font-family: 'Cairo', Tahoma, sans-serif; padding: 24px; color: #1f2937; }
        h1 { font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: right; }
        th { background: #f9fafb; width: 35%; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom:16px"><button onclick="window.print()">طباعة</button></div>
    <h1>طلب القبول — {{ $application->code }}</h1>
    <table>
        <tr><th>اسم الطالب</th><td>{{ $application->student_name ?: '—' }}</td></tr>
        <tr><th>اسم ولي الأمر</th><td>{{ $application->guardian_name ?: '—' }}</td></tr>
        <tr><th>الجوال</th><td>{{ $application->phone ?: '—' }}</td></tr>
        <tr><th>البريد</th><td>{{ $application->email ?: '—' }}</td></tr>
        <tr><th>الهوية</th><td>{{ $application->national_id ?: '—' }}</td></tr>
        <tr><th>الكود الهجري</th><td>{{ $application->hijri_code ?: '—' }}</td></tr>
        <tr><th>تاريخ الميلاد</th><td>{{ optional($application->birth_date)->format('Y-m-d') ?: '—' }}</td></tr>
        <tr><th>المدينة</th><td>{{ $application->city ?: '—' }}</td></tr>
        <tr><th>المرحلة / الصف</th><td>{{ trim(($application->stage ?: '').' '.($application->grade ?: '')) ?: '—' }}</td></tr>
        <tr><th>الحالة</th><td>{{ $application->statusLabel() }}</td></tr>
        <tr><th>الموعد</th><td>{{ $application->appointment_at ? $application->appointment_at->format('Y-m-d H:i') : '—' }}</td></tr>
        <tr><th>تاريخ التقديم</th><td>{{ optional($application->created_at)->format('Y-m-d H:i') }}</td></tr>
    </table>
</body>
</html>
