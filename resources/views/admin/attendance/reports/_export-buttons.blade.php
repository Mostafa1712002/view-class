{{-- #273 — export/print buttons. $report = report key; carries current filters. --}}
@php
    $params = request()->except(['page', 'format']);
@endphp
@if(auth()->user()?->canDo('pdf_export'))
<div class="btn-group btn-group-sm mb-2" role="group" aria-label="تصدير التقرير">
    <a class="btn btn-outline-danger" target="_blank"
       href="{{ route('admin.attendance.reports.export', array_merge(['report' => $report, 'format' => 'pdf'], $params)) }}">
        <i class="la la-file-pdf-o"></i> PDF
    </a>
    <a class="btn btn-outline-success"
       href="{{ route('admin.attendance.reports.export', array_merge(['report' => $report, 'format' => 'excel'], $params)) }}">
        <i class="la la-file-excel-o"></i> Excel
    </a>
    <a class="btn btn-outline-secondary"
       href="{{ route('admin.attendance.reports.export', array_merge(['report' => $report, 'format' => 'csv'], $params)) }}">
        <i class="la la-file-text-o"></i> CSV
    </a>
    <button type="button" class="btn btn-outline-info" onclick="window.print()">
        <i class="la la-print"></i> طباعة
    </button>
</div>
@endif
