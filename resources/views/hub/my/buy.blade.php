@extends('layouts.app')
@section('title', __('hub_my.buy_title', ['name' => $package->name_ar]))
@section('page-title', __('hub_my.buy_title', ['name' => $package->name_ar]))
@section('content')
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
<div class="card"><div class="card-body">
<p><strong>{{ __('hub_my.price', ['price' => $package->price]) }}</strong> — {{ __('hub_my.days', ['days' => $package->duration_days]) }}</p>
<h2 class="h5">{{ __('hub_my.choose_child') }}</h2>
@if($isParent)<a class="btn btn-light mb-3" href="{{ route('my.packages.children.create') }}"><x-svg-icon name="plus-lg" /> {{ __('hub_my.add_child') }}</a>@endif
@forelse($rows as $r)
@php($u = $r['user'])
<div class="border rounded p-3 mb-3"><h3 class="h6">{{ $u->name }}</h3>
@if($r['ready'])
<form method="POST" action="{{ route('my.packages.checkout', $package->id) }}">@csrf
<input type="hidden" name="child_id" value="{{ $u->id }}">
<span class="badge bg-success">{{ __('hub_my.ready') }}</span>
<button class="btn btn-primary ms-2 mr-2">{{ __('hub_my.pay') }}</button></form>
@elseif($r['fixable'])
<p class="text-muted">{{ __('hub_my.complete_data') }}</p>
<form method="POST" action="{{ route('my.packages.children.complete', $u->id) }}">@csrf
<input type="hidden" name="package_id" value="{{ $package->id }}">
@if(in_array('national_id', $r['missing']['student']))
<label for="nid-{{ $u->id }}">{{ __('hub_my.national_id') }}</label><input class="form-control mb-2" id="nid-{{ $u->id }}" name="national_id" inputmode="numeric" maxlength="10" dir="ltr" required>@endif
@if(in_array('gender', $r['missing']['student']))
<label for="gender-{{ $u->id }}">{{ __('hub_my.gender') }}</label><select class="form-control mb-2" id="gender-{{ $u->id }}" name="gender" required>
<option value="male">{{ __('hub_my.male') }}</option><option value="female">{{ __('hub_my.female') }}</option></select>@endif
@if(in_array('stage', $r['missing']['student']))
<label for="stage-{{ $u->id }}">{{ __('hub_my.stage') }}</label><select class="form-control mb-2" id="stage-{{ $u->id }}" name="stage" required>
@foreach(['primary', 'intermediate', 'secondary'] as $s)<option value="{{ $s }}">{{ __('hub_my.stage_'.$s) }}</option>@endforeach</select>@endif
@if(in_array('grade', $r['missing']['student']))
<label for="grade-{{ $u->id }}">{{ __('hub_my.grade') }}</label><input class="form-control mb-2" type="number" min="1" max="12" id="grade-{{ $u->id }}" name="grade" required>@endif
@if(in_array('national_id', $r['missing']['parent']))
<label for="pnid-{{ $u->id }}">{{ __('hub_my.parent_national_id') }}</label><input class="form-control mb-2" id="pnid-{{ $u->id }}" name="parent_national_id" inputmode="numeric" maxlength="10" dir="ltr" required>@endif
<button class="btn btn-primary">{{ __('hub_my.save') }}</button></form>
@else
<div class="alert alert-warning mb-0" role="alert">{{ in_array('parent', $r['missing']['parent']) ? __('hub_my.student_needs_parent') : __('hub_my.needs_support') }}</div>
@endif
</div>
@empty<p>{{ __('hub_my.no_children') }}</p>@endforelse
</div></div>
@endsection
