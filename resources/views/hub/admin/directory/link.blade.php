@extends('layouts.app')
@section('title', __('hub_directory.link_title'))
@section('page-title', __('hub_directory.link_title'))
@section('content')
@php($a = $account)
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
<p class="text-muted">{{ __('hub_directory.never_auto') }}</p>

<h2 class="h5">{{ __('hub_directory.account_details') }}</h2>
<dl>
<dt>{{ __('hub_admin.platform') }} / {{ __('hub_admin.role') }}</dt><dd>{{ $a->platform?->name_ar }} / {{ __('hub_admin.role_'.$a->role) }} <span dir="ltr">#{{ $a->external_id ?? '—' }}</span></dd>
<dt>{{ __('hub_directory.name') }}</dt><dd>{{ $a->name ?? '—' }}</dd>
<dt>{{ __('hub_directory.national_id') }}</dt><dd dir="ltr" class="text-start">{{ $a->national_id ?? '—' }}</dd>
<dt>{{ __('hub_directory.email') }} / {{ __('hub_directory.phone') }}</dt><dd dir="ltr" class="text-start">{{ $a->email ?? '—' }} · {{ $a->phone ?? '—' }}</dd>
@if($a->org_label)<dt>{{ __('hub_directory.org') }}</dt><dd>{{ $a->org_label }}</dd>@endif
<dt>{{ __('hub_admin.status') }}</dt><dd>{{ __('hub_admin.status_'.$a->status) }}@if($a->platform_active === false) — {{ __('hub_directory.platform_inactive') }}@endif</dd>
<dt>{{ __('hub_directory.seen_at') }}</dt><dd>{{ $a->seen_at?->format('Y-m-d H:i') ?? '—' }}</dd>
@if($relatives->isNotEmpty())<dt>{{ $a->role === 'student' ? __('hub_directory.platform_parent') : __('hub_directory.platform_children') }}</dt>
<dd>@foreach($relatives as $r)<a href="{{ $r->user_id ? route('admin.hub.people.show', $r->user_id) : route('admin.hub.accounts.link.form', $r->id) }}">{{ $r->name ?? '#'.$r->external_id }}</a>@if(! $loop->last)، @endif @endforeach</dd>@endif
<dt>{{ __('hub_directory.person') }}</dt><dd>@if($a->user)<a href="{{ route('admin.hub.people.show', $a->user_id) }}">{{ $a->user->name }}</a>@else{{ __('hub_directory.not_linked') }}@endif</dd>
</dl>

@if($a->status === 'conflict')
<h2 class="h5 mt-4">{{ __('hub_directory.conflict_rows') }}</h2>
<div class="table-responsive"><table class="table"><thead><tr><th scope="col">{{ __('hub_directory.name') }}</th><th scope="col">{{ __('hub_directory.external_id') }}</th><th scope="col">{{ __('hub_directory.reason') }}</th><th scope="col"></th></tr></thead><tbody>
@forelse($mirrorRows as $c)
<tr><td>{{ $c['account']->name ?? '—' }}</td><td dir="ltr">{{ $c['account']->external_id }}</td><td>{{ __('hub_directory.reason_'.$c['reason']) }}</td>
<td><form method="POST" action="{{ route('admin.hub.accounts.link', $a->id) }}" onsubmit="return confirm(@js(__('hub_directory.confirm_link')))">@csrf<input type="hidden" name="external_id" value="{{ $c['account']->external_id }}"><button class="btn btn-warning btn-sm">{{ __('hub_directory.link_this') }}</button></form></td></tr>
@empty<tr><td colspan="4">{{ __('hub_directory.no_candidates') }}</td></tr>@endforelse
</tbody></table></div>
@endif

@if($a->user_id === null)
@if($a->role === 'teacher')
<p class="alert alert-info mt-3" role="status">{{ __('hub_directory.teacher_readonly') }}</p>
@else
<h2 class="h5 mt-4">{{ __('hub_directory.candidates') }}</h2>
<form method="GET" class="mb-3"><label for="lookup">{{ __('hub_directory.lookup_label') }}</label>
<div class="input-group"><input class="form-control" id="lookup" name="lookup" value="{{ $lookup }}" dir="ltr"><div class="input-group-append"><button class="btn btn-secondary">{{ __('hub_directory.search') }}</button></div></div></form>
<div class="table-responsive"><table class="table"><thead><tr><th scope="col">{{ __('hub_directory.name') }}</th><th scope="col">{{ __('hub_directory.national_id') }}</th><th scope="col">{{ __('hub_directory.reason') }}</th><th scope="col"></th></tr></thead><tbody>
@forelse($people as $c)
<tr><td><a href="{{ route('admin.hub.people.show', $c['user']->id) }}">{{ $c['user']->name }}</a></td><td dir="ltr">{{ $c['user']->national_id ?? '—' }}</td><td>{{ __('hub_directory.reason_'.$c['reason']) }}</td>
<td><form method="POST" action="{{ route('admin.hub.accounts.link', $a->id) }}" onsubmit="return confirm(@js(__('hub_directory.confirm_link')))">@csrf<input type="hidden" name="user_id" value="{{ $c['user']->id }}"><button class="btn btn-warning btn-sm">{{ __('hub_directory.link_this') }}</button></form></td></tr>
@empty<tr><td colspan="4">{{ __('hub_directory.no_candidates') }}</td></tr>@endforelse
</tbody></table></div>

<h2 class="h5 mt-4">{{ __('hub_directory.create_person') }}</h2>
<p class="text-muted">{{ __('hub_directory.create_hint') }}</p>
<form method="POST" action="{{ route('admin.hub.accounts.create_person', $a->id) }}">@csrf<div class="form-row">
<div class="col-md-4 mb-2"><label for="name">{{ __('hub_directory.name') }}</label><input class="form-control" id="name" name="name" required value="{{ old('name', $a->name) }}"></div>
<div class="col-md-2 mb-2"><label for="gender">{{ __('hub_admin.gender') }}</label><select class="form-control" id="gender" name="gender" required><option value=""></option>
@foreach(['male', 'female'] as $g)<option value="{{ $g }}" @selected(old('gender') === $g)>{{ __('hub_admin.'.$g) }}</option>@endforeach</select></div>
<div class="col-md-2 mb-2"><label for="national_id">{{ __('hub_directory.national_id') }}</label><input class="form-control" id="national_id" name="national_id" dir="ltr" value="{{ old('national_id', $a->national_id) }}"></div>
<div class="col-md-2 mb-2"><label for="email">{{ __('hub_directory.email') }}</label><input class="form-control" id="email" name="email" type="email" dir="ltr" value="{{ old('email', $a->email) }}"></div>
<div class="col-md-2 mb-2"><label for="phone">{{ __('hub_directory.phone') }}</label><input class="form-control" id="phone" name="phone" dir="ltr" value="{{ old('phone', $a->phone) }}"></div>
@if($a->role === 'student')
<div class="col-md-2 mb-2"><label for="stage">{{ __('hub_admin.stage') }}</label><select class="form-control" id="stage" name="stage"><option value=""></option>
@foreach(['primary', 'intermediate', 'secondary'] as $s)<option value="{{ $s }}" @selected(old('stage') === $s)>{{ __('hub_admin.stage_'.$s) }}</option>@endforeach</select></div>
<div class="col-md-2 mb-2"><label for="grade">{{ __('hub_admin.grade') }}</label><input class="form-control" id="grade" name="grade" type="number" min="1" max="12" value="{{ old('grade') }}"></div>
@endif
</div><button class="btn btn-primary">{{ __('hub_directory.create_submit') }}</button></form>
@endif
@endif
</div></div>
@endsection
