@extends('layouts.app')
@section('title', __('special_programs/bank.title'))
@section('page-title', __('special_programs.programs.'.$program))
@php($sp = $detail['sp'])
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'bank'])
<div class="ds-card"><div class="card-body">
    @unless($sp->is_active)<div class="alert alert-warning" role="alert">{{ __('special_programs/bank.inactive_notice') }}</div>@endunless
    <h2 class="h5">{{ $subject->name_ar }}@if($sp->question_order) — #{{ $sp->question_order }}@endif</h2>
    <p style="white-space:pre-line">{{ $detail['bank']->body_ar }}</p>
    @if($detail['bank']->attachment_path)<img class="mb-3" style="max-width:100%;max-height:360px" src="{{ $image($detail['bank']->attachment_path) }}" alt="{{ __('special_programs/bank.current_image') }}">@endif
    <ol class="pr-3">
        @foreach($detail['answers'] as $a)
            <li class="{{ $a->is_correct ? 'text-success font-weight-bold' : '' }}">{{ $a->answer_text }}@if($a->answer_image) <img style="max-height:60px" src="{{ $image($a->answer_image) }}" alt="">@endif @if($a->is_correct)({{ __('special_programs/bank.correct') }})@endif</li>
        @endforeach
    </ol>
    <dl class="row mb-3">
        @if($detail['skill'])<dt class="col-sm-3">{{ __('special_programs/bank.skill') }}</dt><dd class="col-sm-9">{{ $detail['skill'] }}</dd>@endif
        <dt class="col-sm-3">{{ __('special_programs/bank.term') }}</dt><dd class="col-sm-9">{{ $sp->term ? __('special_programs.terms.'.$sp->term) : '—' }}</dd>
        <dt class="col-sm-3">{{ __('special_programs/bank.difficulty') }}</dt><dd class="col-sm-9">{{ __('special_programs.difficulty.'.$sp->difficulty) }}</dd>
        @if($sp->week)<dt class="col-sm-3">{{ __('special_programs/bank.week') }}</dt><dd class="col-sm-9">{{ $sp->week }}</dd>@endif
        <dt class="col-sm-3">{{ __('special_programs/bank.grade_levels') }}</dt><dd class="col-sm-9">{{ implode('، ', $detail['levels']) ?: '—' }}</dd>
    </dl>
    <a class="btn btn-primary btn-sm" href="{{ route('manage.special-programs.bank.edit', [$program, $sp->id]) }}">{{ __('special_programs.buttons.edit') }}</a>
    <a class="btn btn-light btn-sm" href="{{ route('manage.special-programs.bank.index', [$program, 'subject' => $subject->id]) }}">{{ __('special_programs.buttons.back') }}</a>
</div></div>
@endsection
