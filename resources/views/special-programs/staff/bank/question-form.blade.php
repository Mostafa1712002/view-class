@extends('layouts.app')
@section('title', __('special_programs/bank.title'))
@section('page-title', __('special_programs.programs.'.$program))
@php
    $editing = $detail !== null;
    $sp = $editing ? $detail['sp'] : null;
    $bq = $editing ? $detail['bank'] : null;
    $answers = $editing ? $detail['answers']->values() : collect();
    $slots = min(6, max(4, $answers->count()));
    $correct = old('correct_index', $answers->search(fn ($a) => $a->is_correct) === false ? null : $answers->search(fn ($a) => $a->is_correct));
    $levels = old('grade_levels', $editing ? $detail['levels'] : []);
    $action = $editing ? route('manage.special-programs.bank.update', [$program, $sp->id]) : route('manage.special-programs.bank.store', $program);
@endphp
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'bank'])
<div class="ds-card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $e)<p class="mb-1">{{ $e }}</p>@endforeach</div>@endif
<h2 class="h5">{{ $subject->name_ar }} — {{ __('special_programs/bank.'.($editing ? 'edit_question' : 'add_question')) }}</h2>
<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf @if($editing) @method('PUT') @endif
    <input type="hidden" name="sp_subject_id" value="{{ $subject->id }}">
    <div class="form-group"><label for="body_ar">{{ __('special_programs/bank.stem') }}</label>
        <textarea class="form-control" id="body_ar" name="body_ar" rows="4" maxlength="5000">{{ old('body_ar', $bq?->body_ar) }}</textarea></div>
    <div class="form-group"><label for="question_image">{{ __('special_programs/bank.image_optional') }}</label>
        <input type="file" class="form-control-file" id="question_image" name="question_image" accept="image/*">
        @if($bq?->attachment_path)
            <input type="hidden" name="current_image" value="{{ $bq->attachment_path }}">
            <img class="d-block my-2" style="max-height:140px" src="{{ $imageUrl($bq->attachment_path) }}" alt="{{ __('special_programs/bank.current_image') }}">
            <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="remove_image" name="remove_image" value="1" @checked(old('remove_image'))><label class="custom-control-label" for="remove_image">{{ __('special_programs/bank.remove_image') }}</label></div>
        @endif
    </div>
    <div class="form-row">
        @if($program !== 'tahsili')
        <div class="form-group col-md-4"><label for="sp_skill_id">{{ __('special_programs/bank.skill') }}</label>
            <select class="form-control" id="sp_skill_id" name="sp_skill_id" required><option value="">{{ __('special_programs/bank.choose') }}</option>
            @foreach($skills as $k)<option value="{{ $k->id }}" @selected(old('sp_skill_id', $sp?->sp_skill_id) == $k->id)>{{ $k->name }}@if($k->term) ({{ __('special_programs.terms.'.$k->term) }})@endif</option>@endforeach</select></div>
        @endif
        <div class="form-group col-md-3"><label for="term">{{ __('special_programs/bank.term') }}</label>
            <select class="form-control" id="term" name="term" required>
            @foreach(__('special_programs.terms') as $v => $l)<option value="{{ $v }}" @selected(old('term', $sp?->term ?? 'first') === $v)>{{ $l }}</option>@endforeach</select></div>
        <div class="form-group col-md-3"><label for="difficulty">{{ __('special_programs/bank.difficulty') }}</label>
            <select class="form-control" id="difficulty" name="difficulty" required>
            @foreach(__('special_programs.difficulty') as $v => $l)<option value="{{ $v }}" @selected(old('difficulty', $sp?->difficulty ?? 'medium') === $v)>{{ $l }}</option>@endforeach</select></div>
        @if($program === 'qiyas')
        <div class="form-group col-md-2"><label for="week">{{ __('special_programs/bank.week') }}</label><input type="number" min="1" max="19" class="form-control" id="week" name="week" value="{{ old('week', $sp?->week) }}"></div>
        @endif
        @if($program === 'tahsili')
        <div class="form-group col-md-3"><label for="insert_after">{{ __('special_programs/bank.insert_after') }}</label>
            <input type="number" min="0" class="form-control" id="insert_after" name="insert_after" value="{{ old('insert_after') }}">
            <small class="form-text text-muted">{{ $editing ? __('special_programs/bank.current_order', ['n' => $sp->question_order]) : __('special_programs/bank.insert_after_hint') }}</small></div>
        @endif
    </div>
    <fieldset class="form-group"><legend class="h6">{{ __('special_programs/bank.grade_levels') }}</legend>
        @foreach(range(1, 12) as $g)
            <div class="custom-control custom-checkbox custom-control-inline"><input type="checkbox" class="custom-control-input" id="gl{{ $g }}" name="grade_levels[]" value="{{ $g }}" @checked(in_array($g, array_map('intval', (array) $levels), true))><label class="custom-control-label" for="gl{{ $g }}">{{ $g }}</label></div>
        @endforeach
    </fieldset>
    <fieldset class="form-group"><legend class="h6">{{ __('special_programs/bank.options') }}</legend>
        <p class="text-muted small">{{ __('special_programs/bank.options_hint') }}</p>
        @for($i = 0; $i < $slots; $i++)
            @php($a = $answers->get($i))
            <div class="form-row align-items-center mb-2">
                <div class="col-auto"><div class="custom-control custom-radio"><input type="radio" class="custom-control-input" id="c{{ $i }}" name="correct_index" value="{{ $i }}" @checked((string) $correct === (string) $i)><label class="custom-control-label" for="c{{ $i }}">{{ __('special_programs/bank.correct') }}</label></div></div>
                <div class="col"><input class="form-control" name="options_ar[{{ $i }}]" maxlength="1000" value="{{ old('options_ar.'.$i, $a?->answer_text) }}" aria-label="{{ __('special_programs/bank.option_n', ['n' => $i + 1]) }}" placeholder="{{ __('special_programs/bank.option_n', ['n' => $i + 1]) }}"></div>
                <div class="col-md-4">
                    <input type="file" class="form-control-file" name="option_images[{{ $i }}]" accept="image/*" aria-label="{{ __('special_programs/bank.option_image') }}">
                    @if($a?->answer_image)<input type="hidden" name="option_images_keep[{{ $i }}]" value="{{ $a->answer_image }}"><img style="max-height:50px" src="{{ $imageUrl($a->answer_image) }}" alt="">@endif
                </div>
            </div>
        @endfor
    </fieldset>
    @if($editing)
        <input type="hidden" name="is_active" value="0">
        <div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $sp->is_active))><label class="custom-control-label" for="is_active">{{ __('special_programs/bank.active') }}</label></div>
    @endif
    <button class="btn btn-primary">{{ __('special_programs.buttons.save') }}</button>
    <a class="btn btn-light" href="{{ route('manage.special-programs.bank.index', [$program, 'subject' => $subject->id]) }}">{{ __('special_programs.buttons.cancel') }}</a>
</form></div></div>
@endsection
