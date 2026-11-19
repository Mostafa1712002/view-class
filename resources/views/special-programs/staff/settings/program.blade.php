@extends('layouts.app')
@section('title', __('special_programs/settings.global_title'))
@section('page-title', __('special_programs/settings.global_title'))
@section('content')
@php($editable = true)
<div class="card"><div class="card-body">
@include('special-programs.staff.settings._tabs')
<form method="POST" action="{{ route('manage.special-programs.settings.global.update') }}">
@csrf @method('PUT')
<input type="hidden" name="program" value="{{ $program }}">
@include('special-programs.staff.settings._bands')
<h2 class="h5">{{ __('special_programs/settings.grades_title') }}</h2>
<div class="form-group">
@foreach(range(1, 12) as $g)
<div class="custom-control custom-checkbox custom-control-inline">
<input type="checkbox" class="custom-control-input" id="grade_{{ $g }}" name="grade_levels[]" value="{{ $g }}" @checked(in_array($g, old('grade_levels', $gradeLevels)))>
<label class="custom-control-label" for="grade_{{ $g }}">{{ $g }}</label>
</div>
@endforeach
</div>
@if($program === 'tahsili')
<div class="form-group"><label for="weeks_count">{{ __('special_programs/settings.weeks') }}</label>
<input type="number" class="form-control" id="weeks_count" name="weeks_count" min="1" max="60" value="{{ old('weeks_count', $weeks) }}">
<small class="text-muted">{{ __('special_programs/settings.weeks_hint') }}</small></div>
@endif
<h2 class="h5">{{ __('special_programs/settings.allow_title') }}</h2>
<div class="form-group">
@foreach($schools as $s)
<div class="custom-control custom-checkbox">
<input type="checkbox" class="custom-control-input" id="allow_{{ $s->id }}" name="allow_schools[]" value="{{ $s->id }}" @checked(in_array($s->id, $allowed))>
<label class="custom-control-label" for="allow_{{ $s->id }}">{{ $s->name_ar ?: $s->name }}</label>
</div>
@endforeach
</div>
<button class="btn btn-primary">{{ __('special_programs.buttons.save') }}</button>
</form></div></div>
@endsection
