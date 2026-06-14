@php
    $primary   = $brand_primary_color   ?? '#C9A227';
    $secondary = $brand_secondary_color ?? '#14233A';
@endphp
<style>
    /* ── Reset ──────────────────────────────────────────────────── */
    * { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Body: Arabic-first RTL ─────────────────────────────────── */
    body {
        font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
        direction: rtl;
        text-align: right;
        font-size: 10px;
        color: #1e293b;
        line-height: 1.5;
    }

    /* ── Page header bar ─────────────────────────────────────────── */
    .pdf-header {
        background-color: {{ $secondary }};
        padding: 10px 14px 8px;
        margin-bottom: 12px;
    }
    .pdf-header-table {
        width: 100%;
        border-collapse: collapse;
    }
    .pdf-header-table td {
        border: none;
        vertical-align: middle;
        padding: 0;
    }
    .pdf-brand {
        font-size: 8.5px;
        color: #fde68a;
        font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
    }
    .pdf-report-title {
        font-size: 14px;
        font-weight: bold;
        color: #ffffff;
        font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
    }
    .pdf-meta-right {
        text-align: left;
        font-size: 8px;
        color: #fde68a;
        font-family: dejavusans, sans-serif;
        white-space: nowrap;
    }

    /* ── Footer ──────────────────────────────────────────────────── */
    .pdf-footer {
        margin-top: 10px;
        border-top: 1px solid #e5e7eb;
        padding-top: 5px;
        font-size: 8px;
        color: #94a3b8;
        text-align: center;
        font-family: dejavusans, sans-serif;
    }

    /* ── Tables ──────────────────────────────────────────────────── */
    table.pdf-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    table.pdf-table thead tr {
        background-color: #f8f9fa;
    }
    table.pdf-table thead th {
        color: {{ $secondary }};
        font-weight: bold;
        font-size: 8.5px;
        padding: 6px 5px;
        border: 1px solid #dee2e6;
        text-align: center;
        background-color: #f0f4f8;
        font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
    }
    table.pdf-table tbody td {
        padding: 5px 5px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
        color: #1e293b;
        font-size: 9px;
        line-height: 1.45;
        font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
    }
    table.pdf-table tbody tr:nth-child(even) td { background-color: #fafafa; }
    table.pdf-table tbody tr:nth-child(odd)  td { background-color: #ffffff; }
    table.pdf-table tfoot td {
        padding: 5px;
        border: 1px solid #dee2e6;
        background: #f8f9fa;
        font-size: 9px;
        font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
    }

    /* ── Stat boxes ──────────────────────────────────────────────── */
    table.stat-row { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.stat-row td {
        text-align: center;
        padding: 8px 6px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }
    table.stat-row td .stat-value {
        font-size: 16px;
        font-weight: bold;
        color: {{ $primary }};
        display: block;
        font-family: dejavusans, sans-serif;
    }
    table.stat-row td .stat-label {
        font-size: 8.5px;
        color: #666;
        font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
    }

    /* ── Section headers ─────────────────────────────────────────── */
    .section-title {
        background-color: {{ $primary }};
        color: #ffffff;
        padding: 6px 12px;
        font-size: 11px;
        margin-bottom: 8px;
        font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
    }

    /* ── Badges ──────────────────────────────────────────────────── */
    .badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 3px;
        font-size: 8.5px;
        color: #ffffff;
        font-family: dejavusans, sans-serif;
    }
    .badge-success   { background: #198754; }
    .badge-danger    { background: #dc3545; }
    .badge-warning   { background: #ffc107; color: #333; }
    .badge-info      { background: #0dcaf0; color: #333; }
    .badge-primary   { background: {{ $primary }}; }
    .badge-secondary { background: {{ $secondary }}; }
</style>
