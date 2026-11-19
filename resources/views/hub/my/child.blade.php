@extends('layouts.app')
@section('title', __('hub_my.child_title'))
@section('page-title', __('hub_my.child_title'))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
<form method="POST" action="{{ route('my.packages.children.store') }}">@csrf
<label for="name">{{ __('hub_my.name') }}</label><input class="form-control mb-2" id="name" name="name" value="{{ old('name') }}" required>
<label for="gender">{{ __('hub_my.gender') }}</label><select class="form-control mb-2" id="gender" name="gender" required>
<option value="male" @selected(old('gender') === 'male')>{{ __('hub_my.male') }}</option><option value="female" @selected(old('gender') === 'female')>{{ __('hub_my.female') }}</option></select>
<label for="national_id">{{ __('hub_my.national_id') }}</label><input class="form-control mb-2" id="national_id" name="national_id" value="{{ old('national_id') }}" inputmode="numeric" maxlength="10" dir="ltr" required>
<label for="stage">{{ __('hub_my.stage') }}</label><select class="form-control mb-2" id="stage" name="stage" required>
@foreach(['primary', 'intermediate', 'secondary'] as $s)<option value="{{ $s }}" @selected(old('stage') === $s)>{{ __('hub_my.stage_'.$s) }}</option>@endforeach</select>
<label for="grade">{{ __('hub_my.grade') }}</label><input class="form-control mb-3" type="number" min="1" max="12" id="grade" name="grade" value="{{ old('grade') }}" required>
<button class="btn btn-primary">{{ __('hub_my.save') }}</button>
<a class="btn btn-light" href="{{ route('my.packages.index') }}">{{ __('hub_my.back_to_packages') }}</a></form>
</div></div>
@endsection
