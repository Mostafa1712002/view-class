@extends('layouts.app')
@php($t = 'special_programs/student.final_page.')
@section('title', __($t.'title'))
@section('page-title', $exam->title.' — '.__($t.'title'))
@section('content')
<a class="btn btn-outline-secondary btn-sm mb-2" href="{{ route('student.programs.exam', $exam->id) }}">{{ __('special_programs/student.back') }}</a>
<div class="card"><div class="card-body text-center">
    <small class="text-muted">{{ __($t.'overall') }}</small>
    <h1 class="mb-0">{{ rtrim(rtrim(number_format($pct, 2), '0'), '.') }}%</h1>
    <span class="badge badge-{{ $level['color'] }}">{{ $level['name'] }}</span>
    <div class="text-muted mt-1">{{ $correct }} / {{ $items }}</div>
</div></div>
<div class="row">
@foreach($rows as $r)
    <div class="col-md-4 mb-2"><div class="card h-100"><div class="card-body">
        <h6>{{ $r['subject']->subject?->name_ar }}</h6>
        <strong>{{ rtrim(rtrim(number_format($r['attempt']->score_pct, 2), '0'), '.') }}%</strong>
        <div class="text-muted small">{{ $r['attempt']->correct_count }} / {{ $r['attempt']->items_count }}</div>
        <a class="btn btn-outline-primary btn-sm mt-1" href="{{ route('student.programs.result', $r['attempt']->id) }}">{{ __('special_programs/student.result') }}</a>
    </div></div></div>
@endforeach
</div>
@endsection
