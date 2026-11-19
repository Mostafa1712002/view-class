@extends('layouts.app')
@section('title', __('special_programs/teacher.exam_report'))
@section('page-title', $exam->title)
@section('content')
<div class="mb-3">
    <a class="btn btn-light" href="{{ route('manage.special-programs.teacher-reports.index') }}">{{ __('special_programs/teacher.back') }}</a>
    <a class="btn btn-outline-primary" href="{{ route('manage.special-programs.teacher-reports.export', $exam->id) }}"><x-svg-icon name="download" :size="14" /> {{ __('special_programs/teacher.export') }}</a>
</div>
@if($report['students'] === 0)
    <div class="alert alert-warning" role="alert">{{ __('special_programs/teacher.no_results') }}</div>
@else
<div class="row mb-3">
    @foreach(['average' => $report['avg_pct'].'%', 'max' => $report['max_pct'].'%', 'min' => $report['min_pct'].'%', 'students_count' => $report['students']] as $label => $value)
        <div class="col-md-3 col-6 mb-2"><div class="ds-card"><div class="card-body text-center">
            <div class="text-muted small">{{ __('special_programs/teacher.'.$label) }}</div><div class="h4 mb-0">{{ $value }}</div>
        </div></div></div>
    @endforeach
</div>
<div class="row">
    @foreach(['top10', 'weak10'] as $list)
    <div class="col-md-6 mb-3"><div class="ds-card"><div class="card-body">
        <h6>{{ __('special_programs/teacher.'.$list) }}</h6>
        <table class="table table-sm mb-0"><thead><tr><th scope="col">{{ __('special_programs/teacher.student') }}</th><th scope="col">{{ __('special_programs/teacher.pct') }}</th><th scope="col">{{ __('special_programs/teacher.level') }}</th></tr></thead><tbody>
        @foreach($report[$list] as $s)
            <tr><td>{{ $s->student_name }}</td><td>{{ $s->pct }}%</td><td>{{ $s->level['name'] }}</td></tr>
        @endforeach
        </tbody></table>
    </div></div></div>
    @endforeach
</div>
<div class="row">
    <div class="col-md-5 mb-3"><div class="ds-card"><div class="card-body">
        <h6>{{ __('special_programs/teacher.levels') }}</h6>
        <table class="table table-sm mb-0"><thead><tr><th scope="col">{{ __('special_programs/teacher.level') }}</th><th scope="col">{{ __('special_programs/teacher.count') }}</th></tr></thead><tbody>
        @foreach($report['levels'] as $l)<tr><td>{{ $l['name'] }}</td><td>{{ $l['count'] }}</td></tr>@endforeach
        </tbody></table>
    </div></div></div>
    <div class="col-md-7 mb-3"><div class="ds-card"><div class="card-body">
        <h6>{{ __('special_programs/teacher.by_subject') }}</h6>
        <table class="table table-sm mb-0"><thead><tr><th scope="col">{{ __('special_programs/teacher.subject') }}</th><th scope="col">{{ __('special_programs/teacher.attempts') }}</th><th scope="col">{{ __('special_programs/teacher.average') }}</th><th scope="col">{{ __('special_programs/teacher.max') }}</th><th scope="col">{{ __('special_programs/teacher.min') }}</th></tr></thead><tbody>
        @foreach($report['subjects'] as $s)
            <tr><td>{{ $s->subject_name }}</td><td>{{ $s->attempts }}</td><td>{{ round($s->avg_pct, 2) }}%</td><td>{{ round($s->max_pct, 2) }}%</td><td>{{ round($s->min_pct, 2) }}%</td></tr>
        @endforeach
        </tbody></table>
    </div></div></div>
</div>
<div class="ds-card"><div class="card-body">
    <h6>{{ __('special_programs/teacher.skills') }}</h6>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th scope="col">{{ __('special_programs/teacher.student') }}</th><th scope="col">{{ __('special_programs/teacher.skill') }}</th><th scope="col">{{ __('special_programs/teacher.pct') }}</th></tr></thead><tbody>
    @foreach($report['student_skills'] as $s)
        @foreach($s['skills'] as $k)
            <tr><td>{{ $s['student_name'] }}</td><td>{{ $k['skill_name'] }}</td><td>{{ __('special_programs/teacher.correct_of', ['correct' => $k['correct'], 'answers' => $k['answers']]) }}</td></tr>
        @endforeach
    @endforeach
    </tbody></table></div>
</div></div>
@endif
@endsection
