<style>
    body { font-family: xbriyaz, sans-serif; color: #1f2937; }
    h2 { text-align: center; margin: 0 0 4px; font-size: 15px; }
    .meta { text-align: center; color: #6b7280; font-size: 9px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: center; }
    thead th { background: #f1f5f9; font-weight: 700; }
    tbody tr:nth-child(even) td { background: #f8fafc; }
</style>

<h2>@lang('eval_reports.general_manager_title')</h2>
<div class="meta">
    @lang('eval_reports.cols.evaluations'): {{ $count }}
    — {{ $generated->format('Y-m-d H:i') }}
</div>

<table>
    <thead>
        <tr>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
    </thead>
    <tbody>
        @forelse ($lines as $line)
            <tr>@foreach ($line as $cell)<td>{{ $cell }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($headers) }}">—</td></tr>
        @endforelse
    </tbody>
</table>
