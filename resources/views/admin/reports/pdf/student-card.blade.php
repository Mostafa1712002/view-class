<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بطاقة الطالب - {{ $student->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            padding: 20px;
            border-bottom: 3px solid #0d6efd;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #0d6efd;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .student-info {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .student-info table {
            width: 100%;
        }
        .student-info td {
            padding: 5px 10px;
        }
        .student-info .label {
            color: #666;
            font-size: 11px;
        }
        .student-info .value {
            font-weight: bold;
        }
        .stats-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
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
            font-size: 20px;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .stat-box p {
            font-size: 10px;
            color: #666;
        }
        .section-title {
            background: #0d6efd;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        table.grades {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.grades th,
        table.grades td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: center;
        }
        table.grades th {
            background: #e9ecef;
            font-weight: bold;
        }
        table.grades tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            color: white;
        }
        .badge-success { background: #198754; }
        .badge-danger { background: #dc3545; }
        .footer {
            text-align: center;
            padding: 15px;
            border-top: 1px solid #dee2e6;
            color: #666;
            font-size: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>بطاقة الطالب</h1>
        <p>{{ $academicYear?->name }}</p>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td style="width: 33%;">
                    <span class="label">اسم الطالب</span><br>
                    <span class="value">{{ $student->name }}</span>
                </td>
                <td style="width: 33%;">
                    <span class="label">الصف</span><br>
                    <span class="value">{{ $enrollment?->classRoom?->name ?? 'غير مسجل' }}</span>
                </td>
                <td style="width: 33%;">
                    <span class="label">المرحلة</span><br>
                    <span class="value">{{ $enrollment?->classRoom?->section?->name ?? '-' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <h3>{{ $grades->count() }}</h3>
            <p>عدد المواد</p>
        </div>
        <div class="stat-box">
            @php $overallAverage = $grades->count() > 0 ? round($grades->avg('average'), 1) : 0; @endphp
            <h3>{{ $overallAverage }}%</h3>
            <p>المعدل العام</p>
        </div>
        <div class="stat-box">
            <h3>{{ $attendanceStats['rate'] }}%</h3>
            <p>نسبة الحضور</p>
        </div>
        <div class="stat-box">
            <h3>{{ $attendanceStats['absent'] }}</h3>
            <p>أيام الغياب</p>
        </div>
    </div>

    <div class="section-title">الدرجات حسب المادة</div>
    @if($grades->count() > 0)
        <table class="grades">
            <thead>
                <tr>
                    <th>المادة</th>
                    <th>الفترة الأولى</th>
                    <th>الفترة الثانية</th>
                    <th>الفترة الثالثة</th>
                    <th>الفترة الرابعة</th>
                    <th>المعدل</th>
                    <th>@lang('common.status')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grades as $subjectData)
                    <tr>
                        <td style="text-align: right;">{{ $subjectData['subject']->name }}</td>
                        <td>{{ $subjectData['terms']->get('الفترة الأولى')?->total ?? '-' }}</td>
                        <td>{{ $subjectData['terms']->get('الفترة الثانية')?->total ?? '-' }}</td>
                        <td>{{ $subjectData['terms']->get('الفترة الثالثة')?->total ?? '-' }}</td>
                        <td>{{ $subjectData['terms']->get('الفترة الرابعة')?->total ?? '-' }}</td>
                        <td><strong>{{ $subjectData['average'] }}%</strong></td>
                        <td>
                            <span class="badge badge-{{ $subjectData['average'] >= 50 ? 'success' : 'danger' }}">
                                {{ $subjectData['average'] >= 50 ? 'ناجح' : 'راسب' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align: right;"><strong>المعدل العام</strong></td>
                    <td colspan="4"></td>
                    <td><strong>{{ $overallAverage }}%</strong></td>
                    <td>
                        <span class="badge badge-{{ $overallAverage >= 50 ? 'success' : 'danger' }}">
                            {{ $overallAverage >= 50 ? 'ناجح' : 'راسب' }}
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="text-align: center; padding: 20px; color: #666;">لا توجد درجات مسجلة</p>
    @endif

    <div class="section-title">إحصائيات الحضور</div>
    <table class="grades">
        <tr>
            <th>إجمالي الأيام</th>
            <th>حاضر</th>
            <th>غائب</th>
            <th>متأخر</th>
            <th>بعذر</th>
            <th>نسبة الحضور</th>
        </tr>
        <tr>
            <td>{{ $attendanceStats['total'] }}</td>
            <td>{{ $attendanceStats['present'] }}</td>
            <td>{{ $attendanceStats['absent'] }}</td>
            <td>{{ $attendanceStats['late'] }}</td>
            <td>{{ $attendanceStats['excused'] }}</td>
            <td><strong>{{ $attendanceStats['rate'] }}%</strong></td>
        </tr>
    </table>

    <div class="footer">
        <p>تم إنشاء هذا التقرير بتاريخ {{ now()->format('Y-m-d H:i') }}</p>
        <p>المنصة الذهبية للتعليم</p>
    </div>
</body>
</html>
