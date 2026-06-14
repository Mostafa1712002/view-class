@php
    $pdf_title = 'قائمة الطلاب';
    $pdf_date  = now()->format('Y-m-d');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $pdf_title }}</title>
    @include('partials.pdf.styles')
</head>
<body>

@include('partials.pdf.header')

<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:30px;">#</th>
            <th style="text-align:right;">الاسم</th>
            <th>اسم المستخدم</th>
            <th>البريد الإلكتروني</th>
            <th>الصف</th>
            <th>المرحلة</th>
            <th>تاريخ التسجيل</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $i => $student)
        <tr>
            <td style="text-align:center; font-family:dejavusans;">{{ $i + 1 }}</td>
            <td style="text-align:right;">{{ $student->name }}</td>
            <td style="font-family:dejavusans; direction:ltr; text-align:left;">{{ $student->username ?? '—' }}</td>
            <td style="font-family:dejavusans; direction:ltr; text-align:left; font-size:8px;">{{ $student->email ?? '—' }}</td>
            <td>{{ $student->classRoom?->name ?? '—' }}</td>
            <td>{{ $student->classRoom?->section?->name ?? '—' }}</td>
            <td style="font-family:dejavusans; direction:ltr; text-align:left;">{{ $student->created_at->format('Y/m/d') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@include('partials.pdf.footer')

</body>
</html>
