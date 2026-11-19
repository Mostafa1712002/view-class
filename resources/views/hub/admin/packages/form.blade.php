@extends('layouts.app')
@section('title', __('hub_admin.packages'))
@section('page-title', __('hub_admin.packages'))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="POST" action="{{ $package->exists ? route('admin.hub.packages.update', $package->id) : route('admin.hub.packages.store') }}">
@csrf
@if($package->exists) @method('PUT') @endif
<label for="name_ar">{{ __('hub_admin.name_ar') }}</label><input class="form-control" id="name_ar" name="name_ar" required value="{{ old('name_ar', $package->name_ar) }}">
<label for="name_en">{{ __('hub_admin.name_en') }}</label><input class="form-control" id="name_en" name="name_en" value="{{ old('name_en', $package->name_en) }}">
<label for="description_ar">{{ __('hub_admin.description_ar') }}</label><textarea class="form-control" id="description_ar" name="description_ar" rows="3">{{ old('description_ar', $package->description_ar) }}</textarea>
<div class="form-row">
<div class="col-md-4"><label for="price">{{ __('hub_admin.price') }}</label><input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required value="{{ old('price', $package->price) }}"></div>
<div class="col-md-4"><label for="duration_days">{{ __('hub_admin.duration_days') }}</label><input type="number" min="1" class="form-control" id="duration_days" name="duration_days" required value="{{ old('duration_days', $package->duration_days) }}"></div>
<div class="col-md-4"><label for="sort">{{ __('hub_admin.sort') }}</label><input type="number" min="0" class="form-control" id="sort" name="sort" value="{{ old('sort', $package->sort) }}"></div>
</div>
<fieldset class="mt-3"><legend class="h6">{{ __('hub_admin.items') }}</legend>
@php($chosen = old('items', $selected))
@foreach($platforms as $platform)
<p class="mb-1"><strong>{{ $platform->name_ar }}</strong></p>
@foreach($platform->products ?? [] as $product)
@php($value = $platform->id.'|'.$product['code'])
<div class="custom-control custom-checkbox">
<input type="checkbox" class="custom-control-input" id="item-{{ $platform->id }}-{{ $product['code'] }}" name="items[]" value="{{ $value }}" @checked(in_array($value, (array) $chosen, true))>
<label class="custom-control-label" for="item-{{ $platform->id }}-{{ $product['code'] }}">{{ $product['name_ar'] ?? $product['code'] }}</label></div>
@endforeach
@endforeach
</fieldset>
@foreach(['is_purchasable' => false, 'is_active' => true] as $f => $default)
<div class="custom-control custom-checkbox mt-2">
<input type="hidden" name="{{ $f }}" value="0"><input type="checkbox" class="custom-control-input" id="{{ $f }}" name="{{ $f }}" value="1" @checked(old($f, $package->exists ? $package->$f : $default))>
<label class="custom-control-label" for="{{ $f }}">{{ __('hub_admin.'.$f) }}</label></div>
@endforeach
<button class="btn btn-primary mt-3">{{ __('hub_admin.save') }}</button>
<a href="{{ route('admin.hub.packages.index') }}">{{ __('hub_admin.back') }}</a>
</form></div></div>
@endsection
