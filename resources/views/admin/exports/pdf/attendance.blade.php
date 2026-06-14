@php
    $pdf_title = 'تقرير الحضور المُصدَّر';
    $pdf_date  = ($dateFrom ?? '') . ($dateTo ? ' — ' . $dateTo : '');
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
            <th style="width:25px;">#</th>
            <th style="text-align:right;">الطالب</th>
            <th>التاريخ</th>
            <th>الحالة</th>
            <th style="text-align:right;">ملاحظة</th>
        </tr>
    </thead>
    <tbody>
        @forelse($attendance as $i => $record)
        @php
            $statusMap = ['present' => 'حاضر', 'absent' => 'غائب', 'late' => 'متأخر', 'excused' => 'بعذر'];
            $badgeMap  = ['present' => 'success', 'absent' => 'danger', 'late' => 'warning', 'excused' => 'info'];
            $s = $record->status ?? '';
        @endphp
        <tr>
            <td style="text-align:center; font-family:dejavusans;">{{ $i + 1 }}</td>
            <td style="text-align:right;">{{ optional($record->student)->name ?? '—' }}</td>
            <td style="font-family:dejavusans; direction:ltr; text-align:left;">{{ optional($record->date)->format('Y-m-d') ?? '—' }}</td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $badgeMap[$s] ?? 'secondary' }}">{{ $statusMap[$s] ?? $s }}</span>
            </td>
            <td style="text-align:right;">{{ $record->notes ?? '' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; color:#666; padding:20px;">لا توجد سجلات</td></tr>
        @endforelse
    </tbody>
</table>

@include('partials.pdf.footer')

</body>
</html>
