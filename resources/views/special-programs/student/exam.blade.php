@extends('layouts.app')
@section('title', $exam->title)
@section('page-title', $exam->title)
@section('content')
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="d-flex justify-content-between mb-2">
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('student.programs.index') }}">{{ __('special_programs/student.back') }}</a>
    @if($rows->isNotEmpty() && $rows->every(fn ($r) => $r['attempt'] && $r['attempt']->status !== 'in_progress'))
        <a class="btn btn-success btn-sm" href="{{ route('student.programs.final', $exam->id) }}">{{ __('special_programs/student.final_result') }}</a>
    @endif
</div>
<div class="row">
@foreach($rows as $r)
    @php($es = $r['subject'])
    @php($a = $r['attempt'])
    <div class="col-md-6 col-xl-4 mb-2">
        <div class="card h-100"><div class="card-body">
            <h5>{{ $es->subject?->name_ar }}</h5>
            <div class="text-muted small mb-2">{{ __('special_programs/student.minutes', ['n' => $es->duration_minutes]) }} · {{ __('special_programs/student.questions', ['n' => $es->total_questions]) }}</div>
            @if($a && $a->status !== 'in_progress')
                <span class="badge badge-success mb-1">{{ __('special_programs.attempt_status.'.$a->status) }} · {{ rtrim(rtrim(number_format($a->score_pct, 2), '0'), '.') }}%</span><br>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('student.programs.result', $a->id) }}">{{ __('special_programs/student.result') }}</a>
            @elseif($a)
                <a class="btn btn-warning btn-sm" href="{{ route('student.programs.take', $a->id) }}">{{ __('special_programs/student.resume') }}</a>
            @elseif($r['locked'])
                <span class="text-muted small"><x-svg-icon name="lock" :size="14" /> {{ __('special_programs/student.locked_short') }}</span>
            @elseif($r['expired'])
                <span class="text-muted small">{{ __('special_programs/student.expired_short') }}</span>
            @else
                <form method="POST" action="{{ route('student.programs.start', [$exam->id, $es->id]) }}">@csrf
                    <button class="btn btn-primary btn-sm"><x-svg-icon name="play-fill" :size="14" /> {{ __('special_programs/student.start') }}</button>
                </form>
            @endif
        </div></div>
    </div>
@endforeach
</div>
@endsection
