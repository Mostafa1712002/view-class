@php
    $pdf_title  = 'بطاقة الطالب';
    $pdf_school = optional($enrollment?->classRoom?->section)->name ?? '';
    $pdf_date   = now()->format('Y-m-d');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة الطالب — {{ $student->name }}</title>
    @include('partials.pdf.styles')
</head>
<body>

@include('partials.pdf.header')

{{-- Student info --}}
<table style="width:100%; margin-bottom:12px; border-collapse:collapse; font-size:9.5px;">
    <tr>
        <td style="width:33%; padding:6px 8px; background:#f8f9fa; border:1px solid #dee2e6;">
            <span style="color:#666; font-size:8px; display:block;">اسم الطالب</span>
            <strong>{{ $student->name }}</strong>
        </td>
        <td style="width:33%; padding:6px 8px; background:#f8f9fa; border:1px solid #dee2e6;">
            <span style="color:#666; font-size:8px; display:block;">الصف</span>
            <strong>{{ $enrollment?->classRoom?->name ?? 'غير مسجل' }}</strong>
        </td>
        <td style="width:33%; padding:6px 8px; background:#f8f9fa; border:1px solid #dee2e6;">
            <span style="color:#666; font-size:8px; display:block;">المرحلة</span>
            <strong>{{ $enrollment?->classRoom?->section?->name ?? '—' }}</strong>
        </td>
    </tr>
    <tr>
        <td style="padding:6px 8px; background:#f8f9fa; border:1px solid #dee2e6;">
            <span style="color:#666; font-size:8px; display:block;">العام الدراسي</span>
            <strong>{{ $academicYear?->name ?? '—' }}</strong>
        </td>
        <td style="padding:6px 8px; background:#f8f9fa; border:1px solid #dee2e6;">
            <span style="color:#666; font-size:8px; display:block;">اسم المستخدم</span>
            <strong>{{ $student->username ?? '—' }}</strong>
        </td>
        <td style="padding:6px 8px; background:#f8f9fa; border:1px solid #dee2e6;"></td>
    </tr>
</table>

{{-- KPIs --}}
@php $overallAverage = $grades->count() > 0 ? round($grades->avg('average'), 1) : 0; @endphp
<table class="stat-row">
    <tr>
        <td>
            <span class="stat-value">{{ $grades->count() }}</span>
            <span class="stat-label">عدد المواد</span>
        </td>
        <td>
            <span class="stat-value">{{ $overallAverage }}%</span>
            <span class="stat-label">المعدل العام</span>
        </td>
        <td>
            <span class="stat-value">{{ $attendanceStats['rate'] }}%</span>
            <span class="stat-label">نسبة الحضور</span>
        </td>
        <td>
            <span class="stat-value" style="color:#dc3545;">{{ $attendanceStats['absent'] }}</span>
            <span class="stat-label">أيام الغياب</span>
        </td>
    </tr>
</table>

<div class="section-title">الدرجات حسب المادة</div>

@if($grades->count() > 0)
<table class="pdf-table">
    <thead>
        <tr>
            <th style="text-align:right;">المادة</th>
            <th>الفترة الأولى</th>
            <th>الفترة الثانية</th>
            <th>الفترة الثالثة</th>
            <th>الفترة الرابعة</th>
            <th>المعدل</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($grades as $subjectData)
        <tr>
            <td style="text-align:right;">{{ $subjectData['subject']->name }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $subjectData['terms']->get('الفترة الأولى')?->total ?? '—' }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $subjectData['terms']->get('الفترة الثانية')?->total ?? '—' }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $subjectData['terms']->get('الفترة الثالثة')?->total ?? '—' }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $subjectData['terms']->get('الفترة الرابعة')?->total ?? '—' }}</td>
            <td style="text-align:center;"><strong>{{ $subjectData['average'] }}%</strong></td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $subjectData['average'] >= 50 ? 'success' : 'danger' }}">
                    {{ $subjectData['average'] >= 50 ? 'ناجح' : 'راسب' }}
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td style="text-align:right;"><strong>المعدل العام</strong></td>
            <td colspan="4"></td>
            <td style="text-align:center;"><strong>{{ $overallAverage }}%</strong></td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $overallAverage >= 50 ? 'success' : 'danger' }}">
                    {{ $overallAverage >= 50 ? 'ناجح' : 'راسب' }}
                </span>
            </td>
        </tr>
    </tfoot>
</table>
@else
<p style="text-align:center; padding:20px; color:#666;">لا توجد درجات مسجلة</p>
@endif

<div class="section-title" style="margin-top:14px;">إحصائيات الحضور</div>
<table class="pdf-table">
    <thead>
        <tr>
            <th>إجمالي الأيام</th>
            <th>حاضر</th>
            <th>غائب</th>
            <th>متأخر</th>
            <th>بعذر</th>
            <th>نسبة الحضور</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align:center; font-family:dejavusans;">{{ $attendanceStats['total'] }}</td>
            <td style="text-align:center; color:#198754; font-family:dejavusans;">{{ $attendanceStats['present'] }}</td>
            <td style="text-align:center; color:#dc3545; font-family:dejavusans;">{{ $attendanceStats['absent'] }}</td>
            <td style="text-align:center; color:#b45309; font-family:dejavusans;">{{ $attendanceStats['late'] }}</td>
            <td style="text-align:center; color:#0dcaf0; font-family:dejavusans;">{{ $attendanceStats['excused'] }}</td>
            <td style="text-align:center;"><strong>{{ $attendanceStats['rate'] }}%</strong></td>
        </tr>
    </tbody>
</table>

@include('partials.pdf.footer')

</body>
</html>
