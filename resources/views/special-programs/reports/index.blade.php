@extends('layouts.app')
@php $t = fn ($k) => __('special_programs/reports.'.$k); @endphp
@section('title', __('special_programs/reports.page_title', ['program' => \App\Modules\SpecialPrograms\Support\Programs::label($program)]))
@section('page-title', __('special_programs/reports.page_title', ['program' => \App\Modules\SpecialPrograms\Support\Programs::label($program)]))
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'reports'])
@if($allSchools)<div class="alert alert-warning" role="alert">{{ $t('pick_school_note') }}</div>@endif

<form method="GET" class="card mb-3"><div class="card-body"><div class="form-row">
    <div class="form-group col-md-2"><label for="f-grade">{{ $t('filters.grade') }}</label>
        <select id="f-grade" name="grade" class="form-control"><option value="">{{ $t('filters.all') }}</option>
            @foreach($grades as $g)<option value="{{ $g }}" @selected($filters->grade === (int) $g)>{{ $g }}</option>@endforeach</select></div>
    <div class="form-group col-md-2"><label for="f-class">{{ $t('filters.class') }}</label>
        <select id="f-class" name="class_id" class="form-control"><option value="">{{ $t('filters.all') }}</option>
            @foreach($classes as $c)<option value="{{ $c->id }}" @selected($filters->class === (int) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="form-group col-md-2"><label for="f-group">{{ $t('filters.group') }}</label>
        <select id="f-group" name="group_id" class="form-control"><option value="">{{ $t('filters.all') }}</option>
            @foreach($groups as $id => $name)<option value="{{ $id }}" @selected($filters->group === (int) $id)>{{ $name }}</option>@endforeach</select></div>
    <div class="form-group col-md-2"><label for="f-subject">{{ $t('filters.subject') }}</label>
        <select id="f-subject" name="subject_id" class="form-control"><option value="">{{ $t('filters.all') }}</option>
            @foreach($subjects as $id => $name)<option value="{{ $id }}" @selected($filters->subject === (int) $id)>{{ $name }}</option>@endforeach</select></div>
    <div class="form-group col-md-2"><label for="f-origin">{{ $t('filters.origin') }}</label>
        <select id="f-origin" name="origin" class="form-control"><option value="">{{ $t('filters.all') }}</option>
            @foreach(['admin', 'teacher'] as $o)<option value="{{ $o }}" @selected($filters->origin === $o)>{{ __('special_programs.creator_roles.'.$o) }}</option>@endforeach</select></div>
    <div class="form-group col-md-2"><label for="f-exam">{{ $t('filters.exam') }}</label>
        <select id="f-exam" name="exam_id" class="form-control"><option value="">{{ $t('filters.all') }}</option>
            @foreach($examOptions as $id => $name)<option value="{{ $id }}" @selected($filters->exam === (int) $id)>{{ $name }}</option>@endforeach</select></div>
    <div class="form-group col-md-2"><label for="f-from">{{ $t('filters.from') }}</label><input id="f-from" type="date" name="from" class="form-control" value="{{ $filters->from }}"></div>
    <div class="form-group col-md-2"><label for="f-to">{{ $t('filters.to') }}</label><input id="f-to" type="date" name="to" class="form-control" value="{{ $filters->to }}"></div>
    <div class="form-group col-md-8 d-flex align-items-end">
        <button class="btn btn-primary ml-2">{{ $t('filters.apply') }}</button>
        <a class="btn btn-light ml-2" href="{{ route('manage.special-programs.reports.index', $program) }}">{{ $t('filters.reset') }}</a>
        <a class="btn btn-outline-secondary" href="{{ route('manage.special-programs.reports.print', [$program] + request()->query()) }}"><x-svg-icon name="printer" :size="14" /> {{ $t('print') }}</a>
    </div>
</div></div></form>

<div class="card mb-3"><div class="card-body">
    <h3 class="h6">{{ $t('levels_strip') }}</h3>
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach($bands as $b)
            <span class="badge badge-{{ $b['color'] }} ml-2 mb-1"><x-svg-icon :name="$b['icon']" :size="12" /> {{ $b['name'] }} ({{ $b['min'] }}–{{ $b['max'] }}%)</span>
        @endforeach
    </div>
    <canvas id="sp-levels-chart" height="70" aria-label="{{ $t('chart_title') }}"></canvas>
</div></div>

@foreach($sections as $key => $section)
    @include('special-programs.reports.sections.table', ['section' => $section, 'key' => $key])
@endforeach

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var d = @json($chart);
    var palette = {success: '#28a745', info: '#17a2b8', primary: '#007bff', warning: '#ffc107', danger: '#dc3545', secondary: '#6c757d'};
    new Chart(document.getElementById('sp-levels-chart'), {
        type: 'bar',
        data: {labels: d.labels, datasets: [{label: @json($t('chart_count')), data: d.data, backgroundColor: d.colors.map(function (c) { return palette[c] || '#6c757d'; })}]},
        options: {plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true, ticks: {precision: 0}}}}
    });
})();
</script>
@endsection
