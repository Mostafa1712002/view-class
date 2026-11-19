@extends('layouts.app')
@section('title', __('hub_open.title'))
@section('page-title', $credentials['platform']->name_ar)
@section('content')
<div class="card"><div class="card-body">
<p>{{ __('hub_open.no_one_tap') }}</p>
<dl>
<dt>{{ __('hub_open.login') }}</dt><dd dir="ltr">{{ $credentials['login'] ?? '—' }}</dd>
@if($credentials['password'])<dt>{{ __('hub_open.password') }}</dt><dd dir="ltr"><code>{{ $credentials['password'] }}</code></dd>@endif
</dl>
@php($links = (array) ($credentials['platform']->capabilities['app_links'] ?? []))
@if($links)<ul>@foreach($links as $label => $url)<li><a href="{{ $url }}" rel="noopener" target="_blank">{{ $label }}</a></li>@endforeach</ul>@endif
</div></div>
@endsection
