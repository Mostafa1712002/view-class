@extends('layouts.app')
@section('title', __('hub_admin.platforms'))
@section('page-title', __('hub_admin.platforms'))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if(session('new_secret'))
<div class="alert alert-warning" role="alert"><p>{{ __('hub_admin.secret_once') }}</p><code dir="ltr" class="d-block">{{ session('new_secret') }}</code></div>
@endif
<form method="POST" action="{{ $platform->exists ? route('admin.hub.platforms.update', $platform->id) : route('admin.hub.platforms.store') }}">
@csrf
@if($platform->exists) @method('PUT') @endif
@foreach(['slug', 'name_ar', 'name_en', 'base_url'] as $f)
<label for="{{ $f }}">{{ __('hub_admin.'.$f) }}</label>
<input class="form-control" id="{{ $f }}" name="{{ $f }}" value="{{ old($f, $platform->$f) }}" @if($f === 'slug' || $f === 'base_url') dir="ltr" @endif>
@endforeach
@foreach(['products', 'capabilities', 'required_fields'] as $f)
<label for="{{ $f }}" class="mt-2">{{ __('hub_admin.'.$f) }}</label>
<textarea class="form-control" id="{{ $f }}" name="{{ $f }}" rows="4" dir="ltr">{{ old($f, json_encode($platform->$f ?? ($f === 'products' ? [] : new stdClass), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) }}</textarea>
@endforeach
<div class="custom-control custom-checkbox mt-2">
<input type="hidden" name="is_enabled" value="0"><input type="checkbox" class="custom-control-input" id="is_enabled" name="is_enabled" value="1" @checked(old('is_enabled', $platform->is_enabled))>
<label class="custom-control-label" for="is_enabled">{{ __('hub_admin.is_enabled') }}</label></div>
<button class="btn btn-primary mt-3">{{ __('hub_admin.save') }}</button>
<a href="{{ route('admin.hub.platforms.index') }}">{{ __('hub_admin.back') }}</a>
</form>
<hr>
<h2 class="h5">{{ __('hub_admin.secret') }}</h2>
@if($platform->exists)
<p>{{ $platform->secret ? __('hub_admin.secret_configured') : __('hub_admin.secret_missing') }}</p>
<form method="POST" action="{{ route('admin.hub.platforms.secret', $platform->id) }}" class="d-inline">@csrf<button class="btn btn-light">{{ __('hub_admin.generate_secret') }}</button></form>
<form method="POST" action="{{ route('admin.hub.platforms.test', $platform->id) }}" class="d-inline">@csrf<button class="btn btn-light">{{ __('hub_admin.test_connection') }}</button></form>
@else<p>{{ __('hub_admin.platform_save_first') }}</p>@endif
</div></div>
@endsection
