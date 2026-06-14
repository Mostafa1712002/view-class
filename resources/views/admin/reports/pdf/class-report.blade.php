@php
    $pdf_title  = 'تقرير الصف: ' . $class->name;
    $pdf_school = ($class->section->name ?? '') . ($academicYear ? ' — ' . $academicYear->name : '');
    $pdf_date   = now()->format('Y-m-d');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الصف — {{ $class->name }}</title>
    @include('partials.pdf.styles')
</head>
<body>

@include('partials.pdf.header')

@if($subject)
<div style="background:#fffbeb; border:1px solid #fde68a; padding:5px 10px; margin-bottom:10px; font-size:9px; color:#92400e;">
    <strong>المادة المختارة:</strong> {{ $subject->name }}
</div>
@endif

<table class="stat-row">
    <tr>
        <td>
            <span class="stat-value">{{ $studentsData->count() }}</span>
            <span class="stat-label">عدد الطلاب</span>
        </td>
        <td>
            <span class="stat-value">{{ round($classAverage, 1) }}%</span>
            <span class="stat-label">معدل الصف</span>
        </td>
        <td>
            <span class="stat-value">{{ round($classAttendanceRate, 1) }}%</span>
            <span class="stat-label">نسبة الحضور</span>
        </td>
        <td>
            <span class="stat-value" style="color:#198754;">{{ $studentsData->where('average', '>=', 50)->count() }}</span>
            <span class="stat-label">عدد الناجحين</span>
        </td>
    </tr>
</table>

<div class="section-title">ترتيب الطلاب حسب المعدل</div>

@if($studentsData->count() > 0)
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:50px;">الترتيب</th>
            <th style="text-align:right;">اسم الطالب</th>
            <th style="width:80px;">المعدل</th>
            <th style="width:80px;">نسبة الحضور</th>
            <th style="width:60px;">الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($studentsData as $index => $data)
        <tr>
            <td style="text-align:center; font-family:dejavusans;">{{ $index + 1 }}</td>
            <td style="text-align:right;">{{ $data['student']->name }}</td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $data['average'] >= 90 ? 'success' : ($data['average'] >= 70 ? 'primary' : ($data['average'] >= 50 ? 'warning' : 'danger')) }}">
                    {{ $data['average'] }}%
                </span>
            </td>
            <td style="text-align:center; font-family:dejavusans;">{{ $data['attendance_rate'] }}%</td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $data['average'] >= 50 ? 'success' : 'danger' }}">
                    {{ $data['average'] >= 50 ? 'ناجح' : 'راسب' }}
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="text-align:center; padding:20px; color:#666;">لا يوجد طلاب مسجلين</p>
@endif

@include('partials.pdf.footer')

</body>
</html>
