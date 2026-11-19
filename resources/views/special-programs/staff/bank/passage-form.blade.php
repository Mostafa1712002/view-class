@extends('layouts.app')
@section('title', __('special_programs/bank.passages'))
@section('page-title', __('special_programs.programs.'.$program))
@php
    $editing = $passage !== null;
    $first = $members->first()['sp'] ?? null;
    $levels = old('grade_levels', $levels);
    $subs = old('subs');
    if ($subs === null) {
        $subs = $members->map(fn ($m) => [
            'id' => $m['sp']->id, 'body_ar' => $m['bank']->body_ar, 'sp_skill_id' => $m['sp']->sp_skill_id,
            'options_ar' => $m['answers']->pluck('answer_text')->all(),
            'correct_index' => $m['answers']->values()->search(fn ($a) => $a->is_correct) ?: 0,
        ])->all();
    }
    $subs = array_values($subs) ?: [[], []];
    $action = $editing ? route('manage.special-programs.bank.passages.update', [$program, $passage->id]) : route('manage.special-programs.bank.passages.store', $program);
    $skillOptions = fn ($sel) => $skills->map(fn ($k) => '<option value="'.$k->id.'"'.((string) $sel === (string) $k->id ? ' selected' : '').'>'.e($k->name.($k->term ? ' ('.__('special_programs.terms.'.$k->term).')' : '')).'</option>')->implode('');
@endphp
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'bank'])
<div class="ds-card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $e)<p class="mb-1">{{ $e }}</p>@endforeach</div>@endif
<h2 class="h5">{{ $subject->name_ar }} — {{ __('special_programs/bank.'.($editing ? 'edit_passage' : 'add_passage')) }}</h2>
<form method="POST" action="{{ $action }}">
    @csrf @if($editing) @method('PUT') @endif
    <input type="hidden" name="sp_subject_id" value="{{ $subject->id }}">
    <div class="form-group"><label for="passage_text">{{ __('special_programs/bank.passage_text') }}</label>
        <textarea class="form-control" id="passage_text" name="passage_text" rows="8" required>{{ old('passage_text', $passage?->passage_text) }}</textarea></div>
    <div class="form-row">
        @if($program === 'qiyas')
        <div class="form-group col-md-4"><label for="sp_skill_id">{{ __('special_programs/bank.skill') }}</label>
            <select class="form-control" id="sp_skill_id" name="sp_skill_id" required><option value="">{{ __('special_programs/bank.choose') }}</option>{!! $skillOptions(old('sp_skill_id', $first?->sp_skill_id)) !!}</select></div>
        @endif
        <div class="form-group col-md-3"><label for="term">{{ __('special_programs/bank.term') }}</label>
            <select class="form-control" id="term" name="term" required>@foreach(__('special_programs.terms') as $v => $l)<option value="{{ $v }}" @selected(old('term', $first?->term ?? 'first') === $v)>{{ $l }}</option>@endforeach</select></div>
        <div class="form-group col-md-3"><label for="difficulty">{{ __('special_programs/bank.difficulty') }}</label>
            <select class="form-control" id="difficulty" name="difficulty" required>@foreach(__('special_programs.difficulty') as $v => $l)<option value="{{ $v }}" @selected(old('difficulty', $first?->difficulty ?? 'medium') === $v)>{{ $l }}</option>@endforeach</select></div>
        @if($program === 'qiyas')
        <div class="form-group col-md-2"><label for="week">{{ __('special_programs/bank.week') }}</label><input type="number" min="1" max="19" class="form-control" id="week" name="week" value="{{ old('week', $first?->week) }}"></div>
        @endif
    </div>
    <fieldset class="form-group"><legend class="h6">{{ __('special_programs/bank.grade_levels') }}</legend>
        @foreach(range(1, 12) as $g)
            <div class="custom-control custom-checkbox custom-control-inline"><input type="checkbox" class="custom-control-input" id="gl{{ $g }}" name="grade_levels[]" value="{{ $g }}" @checked(in_array($g, array_map('intval', (array) $levels), true))><label class="custom-control-label" for="gl{{ $g }}">{{ $g }}</label></div>
        @endforeach
    </fieldset>
    @if($editing)
        <input type="hidden" name="is_active" value="0">
        <div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $first?->is_active ?? true))><label class="custom-control-label" for="is_active">{{ __('special_programs/bank.active') }}</label></div>
    @endif

    <h3 class="h6">{{ __('special_programs/bank.sub_questions') }}</h3>
    <div id="subs">
    @foreach($subs as $n => $s)
        @include('special-programs.staff.bank._sub', ['n' => $n, 's' => $s, 'skillHtml' => $skillOptions($s['sp_skill_id'] ?? null)])
    @endforeach
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-sub"><x-svg-icon name="plus-lg" :size="14" /> {{ __('special_programs/bank.add_sub') }}</button>
    <template id="sub-tpl">@include('special-programs.staff.bank._sub', ['n' => '__n__', 's' => [], 'skillHtml' => $skillOptions(null)])</template>
    <div>
        <button class="btn btn-primary">{{ __('special_programs.buttons.save') }}</button>
        <a class="btn btn-light" href="{{ route('manage.special-programs.bank.passages', [$program, 'subject' => $subject->id]) }}">{{ __('special_programs.buttons.cancel') }}</a>
    </div>
</form></div></div>
<script>
(function () {
    var box = document.getElementById('subs'), tpl = document.getElementById('sub-tpl').innerHTML, n = box.children.length;
    document.getElementById('add-sub').addEventListener('click', function () {
        box.insertAdjacentHTML('beforeend', tpl.replace(/__n__/g, n++));
    });
})();
</script>
@endsection
