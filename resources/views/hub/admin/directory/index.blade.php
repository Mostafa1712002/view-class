@extends('layouts.app')
@section('title', __('hub_directory.title'))
@section('page-title', __('hub_directory.title'))
@section('content')
<div class="card"><div class="card-body">
<p class="text-muted">{{ __('hub_directory.never_auto') }}</p>
<ul class="nav nav-tabs mb-3">
@foreach(['accounts', 'people'] as $t)
<li class="nav-item"><a class="nav-link @if($tab === $t) active @endif" @if($tab === $t) aria-current="page" @endif href="{{ route('admin.hub.directory.index', ['tab' => $t]) }}">{{ __('hub_directory.tab_'.$t) }}</a></li>
@endforeach
</ul>
<form method="GET" class="mb-3"><input type="hidden" name="tab" value="{{ $tab }}"><div class="form-row align-items-end">
<div class="col-md-2 col-6 mb-2"><label for="platform_id">{{ __('hub_admin.platform') }}</label><select class="form-control" id="platform_id" name="platform_id"><option value="">{{ __('hub_admin.all') }}</option>
@foreach($platforms as $p)<option value="{{ $p->id }}" @selected(request('platform_id') == $p->id)>{{ $p->name_ar }}</option>@endforeach</select></div>
@if($tab === 'accounts')
<div class="col-md-2 col-6 mb-2"><label for="role">{{ __('hub_admin.role') }}</label><select class="form-control" id="role" name="role"><option value="">{{ __('hub_admin.all') }}</option>
@foreach(['student', 'parent', 'teacher'] as $r)<option value="{{ $r }}" @selected(request('role') === $r)>{{ __('hub_admin.role_'.$r) }}</option>@endforeach</select></div>
<div class="col-md-2 col-6 mb-2"><label for="status">{{ __('hub_admin.status') }}</label><select class="form-control" id="status" name="status"><option value="">{{ __('hub_admin.all') }}</option>
@foreach(['unlinked', 'conflict', 'active', 'pending', 'needs_info', 'failed', 'disabled', 'missing'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('hub_admin.status_'.$s) }}</option>@endforeach</select></div>
<div class="col-md-2 col-6 mb-2"><label for="linked">{{ __('hub_directory.linked_filter') }}</label><select class="form-control" id="linked" name="linked"><option value="">{{ __('hub_admin.all') }}</option>
<option value="1" @selected(request('linked') === '1')>{{ __('hub_directory.linked_yes') }}</option><option value="0" @selected(request('linked') === '0')>{{ __('hub_directory.linked_no') }}</option></select></div>
@endif
<div class="col-md-3 col-12 mb-2"><label for="q">{{ __('hub_directory.search') }}</label><input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="{{ __('hub_directory.search_hint') }}"></div>
<div class="col-md-1 col-6 mb-2"><button class="btn btn-primary">{{ __('hub_admin.filter') }}</button></div>
</div></form>

@if($tab === 'accounts')
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('hub_directory.name') }}</th><th scope="col">{{ __('hub_admin.platform') }}</th><th scope="col">{{ __('hub_admin.role') }}</th>
<th scope="col">{{ __('hub_directory.national_id') }} / {{ __('hub_directory.email') }} / {{ __('hub_directory.phone') }}</th>
<th scope="col">{{ __('hub_admin.status') }}</th><th scope="col">{{ __('hub_directory.person') }}</th><th scope="col"></th>
</tr></thead><tbody>
@forelse($accounts as $a)
<tr><td>{{ $a->name ?? '—' }}@if($a->org_label)<br><small class="text-muted">{{ $a->org_label }}</small>@endif</td>
<td>{{ $a->platform?->name_ar }}<br><small dir="ltr">#{{ $a->external_id ?? '—' }}</small></td><td>{{ __('hub_admin.role_'.$a->role) }}</td>
<td dir="ltr" class="text-start">{{ $a->national_id ?? '—' }}<br><small>{{ $a->email ?? '—' }} · {{ $a->phone ?? '—' }}</small></td>
<td>{{ __('hub_admin.status_'.$a->status) }}@if($a->platform_active === false)<br><small class="text-danger">{{ __('hub_directory.platform_inactive') }}</small>@endif</td>
<td>@if($a->user)<a href="{{ route('admin.hub.people.show', $a->user_id) }}">{{ $a->user->name }}</a>@else{{ __('hub_directory.not_linked') }}@endif</td>
<td><a href="{{ route('admin.hub.accounts.link.form', $a->id) }}">{{ __('hub_directory.review') }}</a></td></tr>
@empty<tr><td colspan="7">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $accounts->links() }}
@else
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('hub_directory.name') }}</th><th scope="col">{{ __('hub_directory.national_id') }}</th><th scope="col">{{ __('hub_directory.accounts_count') }}</th>
</tr></thead><tbody>
@forelse($people as $u)
<tr><td><a href="{{ route('admin.hub.people.show', $u->id) }}">{{ $u->name }}</a></td><td dir="ltr" class="text-start">{{ $u->national_id ?? '—' }}</td>
<td>@foreach($peopleAccounts->get($u->id, collect()) as $a){{ $a->platform?->name_ar }} ({{ __('hub_admin.role_'.$a->role) }}: {{ __('hub_admin.status_'.$a->status) }})@if(! $loop->last)، @endif @endforeach</td></tr>
@empty<tr><td colspan="3">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $people->links() }}
@endif
</div></div>
@endsection
