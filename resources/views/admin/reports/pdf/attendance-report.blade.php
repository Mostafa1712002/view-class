@php
    $pdf_title  = 'تقرير الحضور الشهري';
    $pdf_school = $class->name . ($class->section ? ' — ' . $class->section->name : '');
    $pdf_date   = \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y');
    $totalPresent = $attendanceData->sum('present');
    $totalAbsent  = $attendanceData->sum('absent');
    $totalLate    = $attendanceData->sum('late');
    $totalExcused = $attendanceData->sum('excused');
    $averageRate  = round($attendanceData->avg('rate'), 1);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الحضور — {{ $class->name }}</title>
    @include('partials.pdf.styles')
</head>
<body>

@include('partials.pdf.header')

<table class="stat-row">
    <tr>
        <td>
            <span class="stat-value" style="color:#198754;">{{ $totalPresent }}</span>
            <span class="stat-label">إجمالي الحضور</span>
        </td>
        <td>
            <span class="stat-value" style="color:#dc3545;">{{ $totalAbsent }}</span>
            <span class="stat-label">إجمالي الغياب</span>
        </td>
        <td>
            <span class="stat-value" style="color:#b45309;">{{ $totalLate }}</span>
            <span class="stat-label">إجمالي التأخر</span>
        </td>
        <td>
            <span class="stat-value" style="color:#0369a1;">{{ $totalExcused }}</span>
            <span class="stat-label">بعذر</span>
        </td>
        <td>
            <span class="stat-value">{{ $averageRate }}%</span>
            <span class="stat-label">متوسط الحضور</span>
        </td>
    </tr>
</table>

<div class="section-title">تفاصيل حضور الطلاب</div>

@if($attendanceData->count() > 0)
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:30px;">#</th>
            <th style="text-align:right;">اسم الطالب</th>
            <th style="width:50px; background:#d1e7dd !important; color:#155724 !important;">حاضر</th>
            <th style="width:50px; background:#f8d7da !important; color:#842029 !important;">غائب</th>
            <th style="width:50px; background:#fff3cd !important; color:#664d03 !important;">متأخر</th>
            <th style="width:50px; background:#cff4fc !important; color:#055160 !important;">بعذر</th>
            <th style="width:50px;">الإجمالي</th>
            <th style="width:65px;">النسبة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendanceData as $index => $data)
        <tr>
            <td style="text-align:center; font-family:dejavusans;">{{ $index + 1 }}</td>
            <td style="text-align:right;">{{ $data['student']->name }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $data['present'] }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $data['absent'] }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $data['late'] }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $data['excused'] }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $data['total'] }}</td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $data['rate'] >= 90 ? 'success' : ($data['rate'] >= 75 ? 'info' : ($data['rate'] >= 60 ? 'warning' : 'danger')) }}">
                    {{ $data['rate'] }}%
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="text-align:center; padding:20px; color:#666;">لا توجد سجلات حضور</p>
@endif

@include('partials.pdf.footer')

</body>
</html>
