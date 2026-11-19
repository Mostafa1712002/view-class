@extends('layouts.app')
@section('title', __('hub_admin.grant_package'))
@section('page-title', __('hub_admin.grant_package'))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="GET" class="mb-3"><label for="parent_national_id">{{ __('hub_admin.parent_lookup') }}</label>
<div class="input-group"><input class="form-control" id="parent_national_id" name="parent_national_id" value="{{ $parentNationalId }}" dir="ltr">
<div class="input-group-append"><button class="btn btn-light">{{ __('hub_admin.search') }}</button></div></div></form>
<form method="POST" action="{{ route('admin.hub.subscriptions.grant.store') }}">
@csrf
<label for="package_id">{{ __('hub_admin.package') }}</label>
<select class="form-control" id="package_id" name="package_id" required>
@foreach($packages as $p)<option value="{{ $p->id }}" @selected(old('package_id') == $p->id)>{{ $p->name_ar }}</option>@endforeach</select>
@if($parentNationalId !== '')
<fieldset class="mt-3"><legend class="h6">{{ __('hub_admin.children') }}</legend>
@forelse($children as $c)
<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="child-{{ $c->id }}" name="child_ids[]" value="{{ $c->id }}" checked>
<label class="custom-control-label" for="child-{{ $c->id }}">{{ $c->name }}</label></div>
@empty<p>{{ __('hub_admin.no_children') }}</p>@endforelse</fieldset>
@endif
<fieldset class="mt-3"><legend class="h6">{{ __('hub_admin.person') }}</legend>
@foreach(['existing', 'new'] as $m)
<div class="custom-control custom-radio custom-control-inline"><input type="radio" class="custom-control-input" id="mode-{{ $m }}" name="mode" value="{{ $m }}" @checked(old('mode', 'existing') === $m)>
<label class="custom-control-label" for="mode-{{ $m }}">{{ __('hub_admin.mode_'.$m) }}</label></div>
@endforeach
<div class="mt-2"><label for="national_ids">{{ __('hub_admin.national_ids') }}</label><textarea class="form-control" id="national_ids" name="national_ids" rows="3" dir="ltr">{{ old('national_ids') }}</textarea></div>
<div class="form-row mt-2">
@foreach(['name', 'national_id', 'grade', 'parent_name', 'parent_national_id'] as $f)
<div class="col-md-4 mb-2"><label for="{{ $f }}">{{ __('hub_admin.'.$f) }}</label><input class="form-control" id="{{ $f }}" name="{{ $f }}" value="{{ old($f) }}"></div>
@endforeach
<div class="col-md-4 mb-2"><label for="gender">{{ __('hub_admin.gender') }}</label><select class="form-control" id="gender" name="gender">@foreach(['male', 'female'] as $g)<option value="{{ $g }}" @selected(old('gender') === $g)>{{ __('hub_admin.'.$g) }}</option>@endforeach</select></div>
<div class="col-md-4 mb-2"><label for="stage">{{ __('hub_admin.stage') }}</label><select class="form-control" id="stage" name="stage">@foreach(['primary', 'intermediate', 'secondary'] as $g)<option value="{{ $g }}" @selected(old('stage') === $g)>{{ __('hub_admin.stage_'.$g) }}</option>@endforeach</select></div>
</div></fieldset>
<div class="form-row">
<div class="col-md-4"><label for="starts_at">{{ __('hub_admin.start_date') }}</label><input type="date" class="form-control" id="starts_at" name="starts_at" value="{{ old('starts_at') }}"></div>
<div class="col-md-8"><label for="note">{{ __('hub_admin.note') }}</label><input class="form-control" id="note" name="note" maxlength="500" value="{{ old('note') }}"></div>
</div>
<button class="btn btn-primary mt-3">{{ __('hub_admin.grant_package') }}</button>
<a href="{{ route('admin.hub.subscriptions.index') }}">{{ __('hub_admin.back') }}</a>
</form></div></div>
@endsection
