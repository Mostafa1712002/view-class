<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير الحضور - {{ $class->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            padding: 15px;
            border-bottom: 3px solid #0dcaf0;
            margin-bottom: 15px;
        }
        .header h1 {
            color: #0dcaf0;
            font-size: 20px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 12px;
        }
        .stats-row {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .stat-box {
            display: table-cell;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .stat-box h3 {
            font-size: 16px;
            margin-bottom: 3px;
        }
        .stat-box p {
            font-size: 9px;
            color: #666;
        }
        .success { color: #198754; }
        .danger { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #0dcaf0; }
        .section-title {
            background: #0dcaf0;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
            margin-bottom: 8px;
        }
        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.report th,
        table.report td {
            border: 1px solid #dee2e6;
            padding: 6px;
            text-align: center;
        }
        table.report th {
            background: #e9ecef;
            font-weight: bold;
            font-size: 10px;
        }
        .th-success { background: #d1e7dd !important; }
        .th-danger { background: #f8d7da !important; }
        .th-warning { background: #fff3cd !important; }
        .th-info { background: #cff4fc !important; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            color: white;
        }
        .badge-success { background: #198754; }
        .badge-danger { background: #dc3545; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-info { background: #0dcaf0; }
        .footer {
            text-align: center;
            padding: 10px;
            border-top: 1px solid #dee2e6;
            color: #666;
            font-size: 9px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير الحضور الشهري</h1>
        <p>{{ $class->name }} - {{ $class->section->name ?? '' }}</p>
        <p>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}</p>
    </div>

    @php
        $totalPresent = $attendanceData->sum('present');
        $totalAbsent = $attendanceData->sum('absent');
        $totalLate = $attendanceData->sum('late');
        $totalExcused = $attendanceData->sum('excused');
        $averageRate = $attendanceData->avg('rate');
    @endphp

    <div class="stats-row">
        <div class="stat-box">
            <h3 class="success">{{ $totalPresent }}</h3>
            <p>إجمالي الحضور</p>
        </div>
        <div class="stat-box">
            <h3 class="danger">{{ $totalAbsent }}</h3>
            <p>إجمالي الغياب</p>
        </div>
        <div class="stat-box">
            <h3 class="warning">{{ $totalLate }}</h3>
            <p>إجمالي التأخر</p>
        </div>
        <div class="stat-box">
            <h3 class="info">{{ $totalExcused }}</h3>
            <p>بعذر</p>
        </div>
        <div class="stat-box">
            <h3>{{ round($averageRate, 1) }}%</h3>
            <p>متوسط الحضور</p>
        </div>
    </div>

    <div class="section-title">تفاصيل حضور الطلاب</div>
    @if($attendanceData->count() > 0)
        <table class="report">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>اسم الطالب</th>
                    <th class="th-success" style="width: 50px;">حاضر</th>
                    <th class="th-danger" style="width: 50px;">غائب</th>
                    <th class="th-warning" style="width: 50px;">متأخر</th>
                    <th class="th-info" style="width: 50px;">بعذر</th>
                    <th style="width: 50px;">الإجمالي</th>
                    <th style="width: 60px;">النسبة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceData as $index => $data)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="text-align: right;">{{ $data['student']->name }}</td>
                        <td>{{ $data['present'] }}</td>
                        <td>{{ $data['absent'] }}</td>
                        <td>{{ $data['late'] }}</td>
                        <td>{{ $data['excused'] }}</td>
                        <td>{{ $data['total'] }}</td>
                        <td>
                            <span class="badge badge-{{ $data['rate'] >= 90 ? 'success' : ($data['rate'] >= 75 ? 'info' : ($data['rate'] >= 60 ? 'warning' : 'danger')) }}">
                                {{ $data['rate'] }}%
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; padding: 20px; color: #666;">لا توجد سجلات حضور</p>
    @endif

    <div class="footer">
        <p>تم إنشاء هذا التقرير بتاريخ {{ now()->format('Y-m-d H:i') }}</p>
        <p>المنصة الذهبية للتعليم</p>
    </div>
</body>
</html>
