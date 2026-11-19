{{-- @include('special-programs.partials.student-report', ['report' => $repo->studentReport($id, $program), 'attemptUrl' => fn ($row) => route(...)]) --}}
{{-- $attemptUrl (optional): callable(attempt row) => url|null. $chartId (optional) when the partial is included twice. --}}
@php
    $h = $report['header'];
    $detailRows = $report['skills'] ?? $report['difficulty'] ?? [];
    $isTahsili = $h['program'] === 'tahsili';
    $chartId = $chartId ?? 'sp-trend-chart';
    $attemptUrl = $attemptUrl ?? fn ($row) => null;
    $t = fn ($k) => __('special_programs/student_report.'.$k);
@endphp
<div class="sp-student-report" dir="rtl">
    <div class="card mb-3"><div class="card-body d-flex flex-wrap align-items-center gap-3 justify-content-between">
        <div>
            <h2 class="h4 mb-1">{{ $h['name'] }}</h2>
            <div class="text-muted">
                {{ $t('program') }}: {{ \App\Modules\SpecialPrograms\Support\Programs::label($h['program']) }}
                @if($h['class_name']) · {{ $t('class') }}: {{ $h['class_name'] }}@endif
                @if($h['group_name']) · {{ $t('group') }}: {{ $h['group_name'] }}@endif
            </div>
        </div>
        <div class="text-center">
            <div class="small text-muted">{{ $t('current_level') }}</div>
            @if($h['level'])
                <span class="badge badge-{{ $h['level']['color'] }} fs-6"><x-svg-icon :name="$h['level']['icon']" :size="14" /> {{ $h['level']['name'] }}</span>
                <div class="small mt-1">{{ $t('mean') }}: {{ number_format($h['mean_pct'], 1) }}%</div>
            @else
                <span class="text-muted">—</span>
            @endif
        </div>
    </div></div>

    @if(empty($report['history']))
        <div class="alert alert-info" role="status">{{ $t('no_data') }}</div>
    @else
        <div class="card mb-3"><div class="card-body">
            <h3 class="h5">{{ $t('trend') }}</h3>
            <canvas id="{{ $chartId }}" height="90" aria-label="{{ $t('trend') }}"></canvas>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h3 class="h5">{{ $t('subjects') }}</h3>
            <div class="table-responsive"><table class="table table-sm">
                <thead><tr><th scope="col">{{ $t('subject') }}</th><th scope="col">{{ $t('attempts') }}</th><th scope="col">{{ $t('last_pct') }}</th><th scope="col">{{ $t('best_pct') }}</th><th scope="col">{{ $t('mean_pct') }}</th></tr></thead>
                <tbody>@foreach($report['subjects'] as $s)
                    <tr><td>{{ $s['subject_name'] }}</td><td>{{ $s['attempts'] }}</td><td>{{ number_format($s['last_pct'], 1) }}%</td><td>{{ number_format($s['best_pct'], 1) }}%</td><td>{{ number_format($s['mean_pct'], 1) }}%</td></tr>
                @endforeach</tbody>
            </table></div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h3 class="h5">{{ $isTahsili ? $t('difficulty') : $t('skills') }}</h3>
            <div class="table-responsive"><table class="table table-sm">
                <thead><tr><th scope="col">{{ $isTahsili ? $t('level') : $t('skill') }}</th><th scope="col">{{ $t('answers') }}</th><th scope="col">{{ $t('correct') }}</th><th scope="col">{{ $t('success') }}</th></tr></thead>
                <tbody>@foreach($detailRows as $r)
                    <tr><td>{{ $isTahsili ? __('special_programs/student_report.difficulties.'.$r->difficulty) : $r->skill_name }}</td><td>{{ (int) $r->answers }}</td><td>{{ (int) $r->correct }}</td>
                    <td><div class="progress" style="height:18px" role="progressbar" aria-valuenow="{{ round($r->success_pct) }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $r->success_pct }}%">{{ number_format($r->success_pct, 1) }}%</div></div></td></tr>
                @endforeach</tbody>
            </table></div>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h3 class="h5">{{ $t('history') }}</h3>
            <div class="table-responsive"><table class="table table-sm">
                <thead><tr><th scope="col">{{ $t('date') }}</th><th scope="col">{{ $t('exam') }}</th><th scope="col">{{ $t('subject') }}</th><th scope="col">{{ $t('score') }}</th><th scope="col">{{ $t('pct') }}</th><th scope="col"></th></tr></thead>
                <tbody>@foreach($report['history'] as $r)
                    <tr><td>{{ \Illuminate\Support\Carbon::parse($r->ended_at)->format('Y-m-d H:i') }}</td><td>{{ $r->exam_title }}</td><td>{{ $r->subject_name }}</td>
                    <td>{{ $r->correct_count }}/{{ $r->items_count }}</td>
                    <td><span class="badge badge-{{ $r->level['color'] }}">{{ number_format($r->score_pct, 1) }}%</span></td>
                    <td>@if($url = $attemptUrl($r))<a href="{{ $url }}">{{ $t('result') }}</a>@endif</td></tr>
                @endforeach</tbody>
            </table></div>
        </div></div>

        @once
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        @endonce
        <script>
            (function () {
                var pts = @json($report['trend']);
                new Chart(document.getElementById(@json($chartId)), {
                    type: 'line',
                    data: {
                        labels: pts.map(function (p) { return (p.ended_at || '').substring(0, 10) + ' · ' + p.subject_name; }),
                        datasets: [{ data: pts.map(function (p) { return p.pct; }), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .25 }]
                    },
                    options: { plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100 } } }
                });
            })();
        </script>
    @endif
</div>
