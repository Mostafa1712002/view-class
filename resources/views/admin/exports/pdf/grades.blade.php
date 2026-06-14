@php
    $pdf_title = 'تقرير الدرجات';
    $pdf_date  = now()->format('Y-m-d');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $pdf_title }}</title>
    @include('partials.pdf.styles')
    <style>
        /* Grades export — landscape, more columns */
        table.pdf-table thead th { font-size: 7.5px; padding: 4px 3px; }
        table.pdf-table tbody td { font-size: 8px; padding: 4px 3px; }
    </style>
</head>
<body>

@include('partials.pdf.header')

<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:25px;">#</th>
            <th style="text-align:right;">الطالب</th>
            <th>المادة</th>
            <th>الاختبار</th>
            <th style="width:50px;">الدرجة</th>
            <th style="width:60px;">الدرجة القصوى</th>
            <th style="width:60px;">النسبة</th>
            <th style="width:70px;">التاريخ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($grades as $i => $grade)
        <tr>
            <td style="text-align:center; font-family:dejavusans;">{{ $i + 1 }}</td>
            <td style="text-align:right;">{{ $grade->student?->name ?? '—' }}</td>
            <td>{{ $grade->subject?->name ?? '—' }}</td>
            <td>{{ $grade->exam?->title ?? '—' }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $grade->score }}</td>
            <td style="text-align:center; font-family:dejavusans;">{{ $grade->max_score }}</td>
            <td style="text-align:center;">
                <span class="badge badge-{{ ($grade->percentage ?? 0) >= 90 ? 'success' : (($grade->percentage ?? 0) >= 60 ? 'primary' : 'danger') }}">
                    {{ number_format($grade->percentage ?? 0, 1) }}%
                </span>
            </td>
            <td style="font-family:dejavusans; direction:ltr; text-align:left;">{{ $grade->created_at->format('Y/m/d') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@include('partials.pdf.footer')

</body>
</html>
