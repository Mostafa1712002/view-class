@extends('layouts.app')
@section('title', __('hub_admin.person_page'))
@section('page-title', $person->name)
@section('content')
@php($shown = session('shown_password'))
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($shown)<div class="alert alert-warning" role="alert">{{ __('hub_admin.password_once') }} <code dir="ltr">{{ $shown[1] }}</code></div>@endif
<dl>
<dt>{{ __('hub_admin.national_id') }}</dt><dd>{{ $person->national_id ?? '—' }}</dd>
@if($profile)<dt>{{ __('hub_admin.stage') }} / {{ __('hub_admin.grade') }}</dt><dd>{{ $profile->stage ? __('hub_admin.stage_'.$profile->stage) : '—' }} / {{ $profile->grade ?? '—' }}</dd>@endif
@if($person->children->isNotEmpty())<dt>{{ __('hub_admin.parent_of') }}</dt><dd>@foreach($person->children as $c)<a href="{{ route('admin.hub.people.show', $c->id) }}">{{ $c->name }}</a>@if(! $loop->last)، @endif @endforeach</dd>@endif
@if($person->parents->isNotEmpty())<dt>{{ __('hub_admin.child_of') }}</dt><dd>@foreach($person->parents as $c)<a href="{{ route('admin.hub.people.show', $c->id) }}">{{ $c->name }}</a>@if(! $loop->last)، @endif @endforeach</dd>@endif
</dl>

<h2 class="h5 mt-4">{{ __('hub_admin.accounts') }}</h2>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['platform', 'role', 'login', 'status', 'last_sync', 'error'] as $h)<th scope="col">{{ __('hub_admin.'.$h) }}</th>@endforeach<th scope="col"></th>
</tr></thead><tbody>
@forelse($accounts as $a)
<tr><td>{{ $a->platform?->name_ar }}</td><td>{{ __('hub_admin.role_'.$a->role) }}</td><td dir="ltr">{{ $a->login ?? '—' }}</td>
<td>{{ __('hub_admin.status_'.$a->status) }}@unless($a->enabled) ({{ __('hub_admin.disable') }})@endunless<br><small>{{ $a->managed ? __('hub_admin.managed') : __('hub_admin.linked_acct') }}</small></td>
<td>{{ $a->synced_at?->format('Y-m-d H:i') ?? '—' }}</td><td>{{ $a->last_error ?? '—' }}</td>
<td>
@if(in_array($a->status, ['failed', 'needs_info']))<form method="POST" action="{{ route('admin.hub.accounts.retry', $a->id) }}" class="d-inline">@csrf<button class="btn btn-link p-0">{{ __('hub_admin.retry') }}</button></form>@endif
<form method="POST" action="{{ route('admin.hub.accounts.toggle', $a->id) }}" class="d-inline">@csrf<button class="btn btn-link p-0">{{ $a->enabled ? __('hub_admin.disable') : __('hub_admin.enable') }}</button></form>
@if($a->managed)
<form method="POST" action="{{ route('admin.hub.accounts.reveal', $a->id) }}" class="d-inline">@csrf<button class="btn btn-link p-0">{{ __('hub_admin.show_password') }}</button></form>
<form method="POST" action="{{ route('admin.hub.accounts.reset', $a->id) }}" class="d-inline" onsubmit="return confirm(@js(__('hub_admin.confirm_reset')))">@csrf<button class="btn btn-link p-0">{{ __('hub_admin.reset_password') }}</button></form>
@endif
@if($a->status === 'conflict')
<div class="alert alert-warning mt-2 mb-0" role="alert">{{ __('hub_admin.conflict_notice') }}
<form method="POST" action="{{ route('admin.hub.accounts.link', $a->id) }}" class="mt-2">@csrf
<label for="external_id-{{ $a->id }}">{{ __('hub_admin.external_id') }}</label>
<input class="form-control" id="external_id-{{ $a->id }}" name="external_id" required dir="ltr" value="{{ $a->external_id ?? $a->conflictExternalId() }}">
<button class="btn btn-warning mt-2">{{ __('hub_admin.link_after_review') }}</button></form></div>
@endif
</td></tr>
@empty<tr><td colspan="7">{{ __('hub_admin.no_accounts') }}</td></tr>@endforelse
</tbody></table></div>

<h2 class="h5 mt-4">{{ __('hub_admin.subscriptions') }}</h2>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['package', 'source', 'status', 'starts_at', 'ends_at'] as $h)<th scope="col">{{ __('hub_admin.'.$h) }}</th>@endforeach</tr></thead><tbody>
@forelse($subscriptions as $s)
<tr><td>{{ $s->package?->name_ar }}</td><td>{{ __('hub_admin.source_'.$s->source) }}</td><td>{{ __('hub_admin.sub_status_'.$s->status) }}</td><td>{{ $s->starts_at?->format('Y-m-d') ?? '—' }}</td><td>{{ $s->ends_at?->format('Y-m-d') ?? '—' }}</td></tr>
@empty<tr><td colspan="5">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>
</div></div>
@endsection
