{{-- mPDF-safe: no JS, no CSS grid/flex; render with default_font xbriyaz, RTL. $report = studentReport() array. --}}
@php
    $h = $report['header'];
    $detailRows = $report['skills'] ?? $report['difficulty'] ?? [];
    $isTahsili = $h['program'] === 'tahsili';
    $t = fn ($k) => __('special_programs/student_report.'.$k);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head><meta charset="utf-8">
<style>
    body { font-family: xbriyaz, sans-serif; direction: rtl; font-size: 11pt; }
    h1 { font-size: 16pt; } h2 { font-size: 13pt; margin-top: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 4px 6px; text-align: right; }
    th { background: #eee; }
</style></head>
<body>
<h1>{{ $t('title') }} — {{ $h['name'] }}</h1>
<p>{{ $t('program') }}: {{ \App\Modules\SpecialPrograms\Support\Programs::label($h['program']) }}
@if($h['class_name']) | {{ $t('class') }}: {{ $h['class_name'] }}@endif
@if($h['group_name']) | {{ $t('group') }}: {{ $h['group_name'] }}@endif<br>
{{ $t('current_level') }}: {{ $h['level']['name'] ?? '—' }}@if($h['mean_pct'] !== null) ({{ number_format($h['mean_pct'], 1) }}%)@endif</p>

@if(empty($report['history']))
    <p>{{ $t('no_data') }}</p>
@else
<h2>{{ $t('subjects') }}</h2>
<table><tr><th>{{ $t('subject') }}</th><th>{{ $t('attempts') }}</th><th>{{ $t('last_pct') }}</th><th>{{ $t('best_pct') }}</th><th>{{ $t('mean_pct') }}</th></tr>
@foreach($report['subjects'] as $s)
<tr><td>{{ $s['subject_name'] }}</td><td>{{ $s['attempts'] }}</td><td>{{ number_format($s['last_pct'], 1) }}%</td><td>{{ number_format($s['best_pct'], 1) }}%</td><td>{{ number_format($s['mean_pct'], 1) }}%</td></tr>
@endforeach</table>

<h2>{{ $isTahsili ? $t('difficulty') : $t('skills') }}</h2>
<table><tr><th>{{ $isTahsili ? $t('level') : $t('skill') }}</th><th>{{ $t('answers') }}</th><th>{{ $t('correct') }}</th><th>{{ $t('success') }}</th></tr>
@foreach($detailRows as $r)
<tr><td>{{ $isTahsili ? __('special_programs/student_report.difficulties.'.$r->difficulty) : $r->skill_name }}</td><td>{{ (int) $r->answers }}</td><td>{{ (int) $r->correct }}</td><td>{{ number_format($r->success_pct, 1) }}%</td></tr>
@endforeach</table>

<h2>{{ $t('history') }}</h2>
<table><tr><th>{{ $t('date') }}</th><th>{{ $t('exam') }}</th><th>{{ $t('subject') }}</th><th>{{ $t('score') }}</th><th>{{ $t('pct') }}</th></tr>
@foreach($report['history'] as $r)
<tr><td>{{ \Illuminate\Support\Carbon::parse($r->ended_at)->format('Y-m-d H:i') }}</td><td>{{ $r->exam_title }}</td><td>{{ $r->subject_name }}</td><td>{{ $r->correct_count }}/{{ $r->items_count }}</td><td>{{ number_format($r->score_pct, 1) }}%</td></tr>
@endforeach</table>
@endif
</body></html>
