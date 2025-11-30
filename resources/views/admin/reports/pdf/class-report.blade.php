<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير الصف - {{ $class->name }}</title>
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
            border-bottom: 3px solid #198754;
            margin-bottom: 15px;
        }
        .header h1 {
            color: #198754;
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
            width: 25%;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .stat-box h3 {
            font-size: 18px;
            color: #198754;
            margin-bottom: 3px;
        }
        .stat-box p {
            font-size: 9px;
            color: #666;
        }
        .section-title {
            background: #198754;
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
        .badge-primary { background: #0d6efd; }
        .rank-gold { background: #ffc107; color: #333; }
        .rank-silver { background: #6c757d; }
        .rank-bronze { background: #cd7f32; }
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
        <h1>تقرير الصف: {{ $class->name }}</h1>
        <p>{{ $class->section->name ?? '' }} - {{ $academicYear?->name }}</p>
        @if($subject)
            <p style="color: #0d6efd;">المادة: {{ $subject->name }}</p>
        @endif
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <h3>{{ $studentsData->count() }}</h3>
            <p>عدد الطلاب</p>
        </div>
        <div class="stat-box">
            <h3>{{ round($classAverage, 1) }}%</h3>
            <p>معدل الصف</p>
        </div>
        <div class="stat-box">
            <h3>{{ round($classAttendanceRate, 1) }}%</h3>
            <p>نسبة الحضور</p>
        </div>
        <div class="stat-box">
            <h3>{{ $studentsData->where('average', '>=', 50)->count() }}</h3>
            <p>عدد الناجحين</p>
        </div>
    </div>

    <div class="section-title">ترتيب الطلاب</div>
    @if($studentsData->count() > 0)
        <table class="report">
            <thead>
                <tr>
                    <th style="width: 40px;">الترتيب</th>
                    <th>اسم الطالب</th>
                    <th style="width: 80px;">المعدل</th>
                    <th style="width: 80px;">نسبة الحضور</th>
                    <th style="width: 60px;">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($studentsData as $index => $data)
                    <tr>
                        <td>
                            @if($index == 0)
                                <span class="badge rank-gold">🥇 1</span>
                            @elseif($index == 1)
                                <span class="badge rank-silver">🥈 2</span>
                            @elseif($index == 2)
                                <span class="badge rank-bronze">🥉 3</span>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </td>
                        <td style="text-align: right;">{{ $data['student']->name }}</td>
                        <td>
                            <span class="badge badge-{{ $data['average'] >= 90 ? 'success' : ($data['average'] >= 70 ? 'primary' : ($data['average'] >= 50 ? 'warning' : 'danger')) }}">
                                {{ $data['average'] }}%
                            </span>
                        </td>
                        <td>{{ $data['attendance_rate'] }}%</td>
                        <td>
                            <span class="badge badge-{{ $data['average'] >= 50 ? 'success' : 'danger' }}">
                                {{ $data['average'] >= 50 ? 'ناجح' : 'راسب' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; padding: 20px; color: #666;">لا يوجد طلاب مسجلين</p>
    @endif

    <div class="footer">
        <p>تم إنشاء هذا التقرير بتاريخ {{ now()->format('Y-m-d H:i') }}</p>
        <p>المنصة الذهبية للتعليم</p>
    </div>
</body>
</html>
