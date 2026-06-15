<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير الرسائل القصيرة</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            direction: rtl;
            text-align: right;
        }
        .report-header {
            text-align: center;
            padding: 12px 0 10px;
            border-bottom: 2px solid #1d4ed8;
            margin-bottom: 12px;
        }
        .report-header h1 {
            font-size: 16px;
            color: #1d4ed8;
            margin-bottom: 4px;
        }
        .report-header .meta {
            font-size: 10px;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        thead th {
            background: #1d4ed8;
            color: #ffffff;
            padding: 6px 8px;
            font-size: 10px;
            font-weight: bold;
            text-align: right;
            border: 1px solid #1e40af;
        }
        tbody tr:nth-child(even) { background: #f1f5f9; }
        tbody tr:nth-child(odd)  { background: #ffffff; }
        tbody td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            vertical-align: middle;
        }
        .status-sent, .status-delivered, .status-read { color: #15803d; font-weight: bold; }
        .status-queued { color: #1d4ed8; font-weight: bold; }
        .status-failed, .status-no_credit, .status-rejected { color: #b91c1c; font-weight: bold; }
        .status-invalid_number, .status-no_number { color: #64748b; }
        .report-footer {
            margin-top: 14px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            width: 25%;
        }
        .summary-item .n { font-size: 14px; font-weight: bold; color: #0f172a; }
        .summary-item .l { font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>تقرير الرسائل القصيرة</h1>
        <div class="meta">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    @php
        $total    = $rows->count();
        $sent     = $rows->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $failed   = $rows->whereIn('status', ['failed', 'no_credit', 'rejected', 'invalid_number', 'no_number'])->count();
        $credit   = $rows->sum('credit_charged');
    @endphp

    <div class="summary">
        <div class="summary-item"><div class="n">{{ $total }}</div><div class="l">الإجمالي</div></div>
        <div class="summary-item"><div class="n" style="color:#15803d;">{{ $sent }}</div><div class="l">مُرسَل</div></div>
        <div class="summary-item"><div class="n" style="color:#b91c1c;">{{ $failed }}</div><div class="l">فاشل</div></div>
        <div class="summary-item"><div class="n">{{ $credit }}</div><div class="l">الرصيد المستهلك</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>المستلم</th>
                <th>رقم الجوال</th>
                <th>الحالة</th>
                <th>عدد الرسائل</th>
                <th>الرصيد</th>
                <th>وقت الإرسال</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $i => $m)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $m->recipient_name ?? '—' }}</td>
                <td dir="ltr" style="text-align:left;">{{ $m->recipient ?? '—' }}</td>
                <td class="status-{{ $m->status }}">{{ $m->statusLabel() }}</td>
                <td style="text-align:center;">{{ $m->message_count ?? 1 }}</td>
                <td style="text-align:center;">{{ $m->credit_charged ?? 0 }}</td>
                <td>{{ optional($m->sent_at ?? $m->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:12px;">لا توجد بيانات.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="report-footer">
        تم إنشاء هذا التقرير تلقائيًا — {{ config('app.name', 'ViewClass') }}
    </div>
</body>
</html>
