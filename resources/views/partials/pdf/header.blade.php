@php
    $secondary   = $brand_secondary_color ?? '#14233A';
    $reportTitle  = $pdf_title  ?? '';
    $reportSchool = $pdf_school ?? '';
    $reportDate   = $pdf_date   ?? now()->format('Y-m-d H:i');
    $logoUrl      = $brand_logo ?? '';
@endphp
<div class="pdf-header">
    <table class="pdf-header-table">
        <tr>
            <td style="width:70%;">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="شعار" style="height:28px; margin-bottom:4px; display:block;">
                @endif
                <div class="pdf-report-title">{{ $reportTitle }}</div>
                <div class="pdf-brand">
                    {{ $brand_name_ar ?? 'المنصة الذهبية' }}
                    @if($reportSchool) &nbsp;·&nbsp; {{ $reportSchool }} @endif
                </div>
            </td>
            <td class="pdf-meta-right">
                {{ $reportDate }}<br>
                طُبع بواسطة: {{ optional(auth()->user())->name ?? '—' }}
            </td>
        </tr>
    </table>
</div>
