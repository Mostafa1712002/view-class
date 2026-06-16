<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $pdf_title ?? 'تقرير' }}</title>
    @include('partials.pdf.styles')
</head>
<body>

@include('partials.pdf.header')

<table class="pdf-table">
    <thead>
        <tr>
            @foreach($headers as $h)
                <th>{{ $h }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td style="text-align:center;">{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($headers) }}" style="text-align:center; padding:14px;">لا توجد بيانات مطابقة للفلاتر.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pdf-footer">
    {{ $pdf_title ?? '' }} — عدد السجلات: {{ count($rows) }}
</div>

</body>
</html>
