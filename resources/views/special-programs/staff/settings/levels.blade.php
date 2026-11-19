@extends('layouts.app')
@section('title', __('special_programs/settings.title'))
@section('page-title', __('special_programs/settings.title'))
@section('content')
<div class="card"><div class="card-body">
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'settings'])
@include('special-programs.staff.settings._tabs', ['tabs' => false])
@unless($editable)<div class="alert alert-warning" role="alert">{{ __('special_programs/settings.read_only') }}</div>@endunless
<form method="POST" action="{{ route('manage.special-programs.settings.update', $program) }}">
@csrf @method('PUT')
@include('special-programs.staff.settings._bands')
@if($program === 'tahsili')
<div class="form-group"><label for="weeks_count">{{ __('special_programs/settings.weeks') }}</label>
<input type="number" class="form-control" id="weeks_count" name="weeks_count" min="1" max="60" value="{{ old('weeks_count', $weeks) }}" @disabled(! $editable)>
<small class="text-muted">{{ __('special_programs/settings.weeks_hint') }}</small></div>
@endif
@if($editable)<button class="btn btn-primary">{{ __('special_programs.buttons.save') }}</button>@endif
</form></div></div>
@endsection
