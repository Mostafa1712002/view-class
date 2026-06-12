<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>الخطة الأسبوعية</title>
    <style>
        /* mPDF-compatible CSS — no flex/grid; tables + inline-block only */
        * { box-sizing: border-box; }
        body {
            /* Arabic-primary content: use xbriyaz (bundled, full Arabic shaping).
               Do NOT force dejavusans here — its Arabic glyphs are unshaped/crude. */
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            direction: rtl;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        /* ── Header bar ───────────────────────────────────────────── */
        .pdf-header {
            background-color: #b45309;
            padding: 10px 14px 8px;
            margin-bottom: 10px;
        }
        .pdf-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .pdf-header-table td {
            padding: 0;
            border: none;
            vertical-align: middle;
        }
        .pdf-title {
            font-size: 15px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.3px;
        }
        .pdf-subtitle {
            font-size: 9px;
            color: #fef3c7;
            margin-top: 2px;
        }
        .pdf-meta-right {
            text-align: left;
            font-size: 8.5px;
            color: #fde68a;
            white-space: nowrap;
        }

        /* ── Week range strip ─────────────────────────────────────── */
        .week-strip {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 5px 10px;
            margin-bottom: 10px;
            font-size: 9.5px;
            color: #92400e;
        }
        .week-strip strong {
            color: #b45309;
        }

        /* ── Data table ───────────────────────────────────────────── */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        table.data-table thead tr {
            background-color: #fef3c7;
        }
        table.data-table thead th {
            background-color: #fef3c7;
            color: #78350f;
            font-weight: bold;
            font-size: 8.5px;
            padding: 6px 5px;
            border: 1px solid #fcd34d;
            text-align: center;
            white-space: nowrap;
        }
        table.data-table tbody td {
            padding: 5px 5px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            color: #1e293b;
            font-size: 9px;
            line-height: 1.45;
        }
        table.data-table tbody tr:nth-child(even) td {
            background-color: #fafafa;
        }
        table.data-table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        /* Status cell */
        .status-prepared {
            background-color: #dcfce7;
            color: #15803d;
            font-weight: bold;
            text-align: center;
            padding: 3px 4px;
            border-radius: 3px;
            font-size: 8.5px;
            white-space: nowrap;
        }
        .status-not-prepared {
            background-color: #fef3c7;
            color: #b45309;
            font-weight: bold;
            text-align: center;
            padding: 3px 4px;
            border-radius: 3px;
            font-size: 8.5px;
            white-space: nowrap;
        }
        .status-locked {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
            text-align: center;
            padding: 3px 4px;
            border-radius: 3px;
            font-size: 8.5px;
            white-space: nowrap;
        }

        /* Wide text cells — allow wrapping */
        .td-wide {
            max-width: 120px;
            word-wrap: break-word;
        }
        .td-narrow {
            max-width: 70px;
            word-wrap: break-word;
        }
        .td-status {
            width: 50px;
            text-align: center;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 30px 10px;
            color: #94a3b8;
            font-size: 11px;
            font-style: italic;
        }

        /* Footer */
        .pdf-footer {
            margin-top: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="pdf-header">
        <table class="pdf-header-table">
            <tr>
                <td class="pdf-title">
                    الخطة الأسبوعية
                    <div class="pdf-subtitle">{{ config('app.name', 'الأول') }}</div>
                </td>
                <td class="pdf-meta-right">
                    {{ $weekStart->format('Y-m-d') }} — {{ $weekEnd->format('Y-m-d') }}<br>
                    طُبع: {{ now()->format('Y-m-d H:i') }}
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Week range ───────────────────────────────────────────────────── --}}
    <div class="week-strip">
        <strong>الأسبوع:</strong>
        من {{ $weekStart->translatedFormat('l d M Y') ?? $weekStart->format('Y-m-d') }}
        إلى {{ $weekEnd->translatedFormat('l d M Y') ?? $weekEnd->format('Y-m-d') }}
        &nbsp;•&nbsp;
        <strong>إجمالي الخطط:</strong> {{ $weekPlans->count() }}
    </div>

    {{-- ── Table ───────────────────────────────────────────────────────── --}}
    @if($weekPlans->isEmpty())
        <div class="empty-state">لا توجد خطط لهذا الأسبوع.</div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th class="td-status">الحالة</th>
                    <th class="td-narrow">المعلم</th>
                    <th class="td-narrow">المادة</th>
                    <th class="td-narrow">الفصل</th>
                    <th class="td-wide">الدرس</th>
                    <th class="td-wide">الأهداف</th>
                    <th class="td-wide">الواجبات والمهام</th>
                    <th class="td-narrow">الاختبارات</th>
                    <th class="td-wide">الملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($weekPlans as $plan)
                    <tr>
                        <td class="td-status">
                            @if($plan->is_locked)
                                <span class="status-locked">مقفلة</span>
                            @elseif($plan->is_prepared)
                                <span class="status-prepared">&#10003; تم</span>
                            @else
                                <span class="status-not-prepared">&#9675; لم يتم</span>
                            @endif
                        </td>
                        <td class="td-narrow">{{ $plan->teacher?->name }}</td>
                        <td class="td-narrow">{{ $plan->subject?->name }}</td>
                        <td class="td-narrow">{{ $plan->classRoom?->name }}</td>
                        <td class="td-wide">{{ $plan->lesson_title ?: $plan->topics }}</td>
                        <td class="td-wide">{{ $plan->objectives }}</td>
                        <td class="td-wide">{{ $plan->homework }}</td>
                        <td class="td-narrow">{{ $plan->exams }}</td>
                        <td class="td-wide">{{ $plan->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── Footer ──────────────────────────────────────────────────────── --}}
    <div class="pdf-footer">
        {{ config('app.name', 'الأول') }} &nbsp;•&nbsp; {{ config('app.url') }} &nbsp;•&nbsp; {{ now()->format('Y') }}
    </div>

</body>
</html>
