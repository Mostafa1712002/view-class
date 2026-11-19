{{-- mPDF-safe: no JS, xbriyaz, RTL. $sections, $bands, $program --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head><meta charset="utf-8">
<style>
    body { font-family: xbriyaz, sans-serif; direction: rtl; font-size: 9pt; }
    h1 { font-size: 15pt; } h2 { font-size: 12pt; margin-top: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 3px 5px; text-align: right; }
    th { background: #eee; }
</style></head>
<body>
<h1>{{ __('special_programs/reports.page_title', ['program' => \App\Modules\SpecialPrograms\Support\Programs::label($program)]) }}</h1>
<p>{{ __('special_programs/reports.levels_strip') }}:
    @foreach($bands as $b){{ $b['name'] }} ({{ $b['min'] }}–{{ $b['max'] }}%) @if(! $loop->last)·@endif @endforeach</p>
@foreach($sections as $section)
    <h2>{{ $section['title'] }}</h2>
    @if(empty($section['rows']))
        <p>{{ __('special_programs/reports.no_rows') }}</p>
    @else
    <table><thead><tr>@foreach($section['headers'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    <tbody>@foreach($section['rows'] as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table>
    @endif
@endforeach
</body></html>
