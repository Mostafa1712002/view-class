@extends('layouts.app')
@section('title', __('hub_overview.title', ['name' => $platform->name_ar]))
@section('page-title', __('hub_overview.title', ['name' => $platform->name_ar]))
@section('content')
<div class="card"><div class="card-body">
<ul class="nav nav-tabs mb-3">
@foreach(array_filter(['packages', 'subscribers', $isSuper ? 'students' : null]) as $t)
<li class="nav-item"><a class="nav-link @if($tab === $t) active @endif" @if($tab === $t) aria-current="page" @endif href="{{ route('admin.hub.platforms.overview', [$platform->slug, 'tab' => $t]) }}">{{ __('hub_overview.tab_'.$t) }}</a></li>
@endforeach
</ul>

@if($tab === 'packages')
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('hub_overview.package') }}</th><th scope="col">{{ __('hub_overview.products') }}</th><th scope="col">{{ __('hub_overview.price_vat') }}</th>
<th scope="col">{{ __('hub_overview.duration') }}</th><th scope="col">{{ __('hub_overview.purchasable') }}</th><th scope="col">{{ __('hub_admin.status') }}</th><th scope="col">{{ __('hub_overview.active_subscribers') }}</th>
</tr></thead><tbody>
@forelse($packages as $p)
<tr><td>{{ $p->name_ar }}</td><td>{{ implode('، ', $p->product_names) }}</td><td>{{ $p->price }} {{ $p->currency }}</td>
<td>{{ __('hub_overview.days', ['n' => $p->duration_days]) }}</td><td>{{ __($p->is_purchasable ? 'hub_overview.yes' : 'hub_overview.no') }}</td>
<td>{{ __($p->is_active && ! $p->trashed() ? 'hub_overview.active' : 'hub_overview.inactive') }}</td><td>{{ $p->active_subscribers }}</td></tr>
@empty<tr><td colspan="7">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>

@elseif($tab === 'subscribers')
<form method="GET" class="mb-3"><div class="form-row align-items-end">
<div class="col-md-3 col-6 mb-2"><label for="package_id">{{ __('hub_overview.package') }}</label><select class="form-control" id="package_id" name="package_id"><option value="">{{ __('hub_admin.all') }}</option>
@foreach($filterPackages as $p)<option value="{{ $p->id }}" @selected(request('package_id') == $p->id)>{{ $p->name_ar }}</option>@endforeach</select></div>
<div class="col-md-2 col-6 mb-2"><label for="status">{{ __('hub_overview.sub_status') }}</label><select class="form-control" id="status" name="status"><option value="">{{ __('hub_admin.all') }}</option>
@foreach(['pending', 'active', 'expired'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('hub_admin.sub_status_'.$s) }}</option>@endforeach</select></div>
<div class="col-md-2 col-6 mb-2"><label for="account_status">{{ __('hub_overview.account_status') }}</label><select class="form-control" id="account_status" name="account_status"><option value="">{{ __('hub_admin.all') }}</option>
@foreach(['active', 'pending', 'conflict', 'needs_info', 'failed', 'disabled'] as $s)<option value="{{ $s }}" @selected(request('account_status') === $s)>{{ __('hub_admin.status_'.$s) }}</option>@endforeach
<option value="none" @selected(request('account_status') === 'none')>{{ __('hub_overview.no_account') }}</option></select></div>
<div class="col-md-3 col-12 mb-2"><label for="q">{{ __('hub_overview.search') }}</label><input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="{{ __('hub_overview.search_hint') }}"></div>
<div class="col-md-1 col-6 mb-2"><button class="btn btn-primary">{{ __('hub_admin.filter') }}</button></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['student', 'parent', 'package', 'period', 'sub_status', 'account_status'] as $h)<th scope="col">{{ __('hub_overview.'.$h) }}</th>@endforeach
</tr></thead><tbody>
@forelse($subs as $s)
@php($acc = $accounts->get($s->user_id))
<tr><td>@if($isSuper)<a href="{{ route('admin.hub.people.show', $s->user_id) }}">{{ $s->user?->name }}</a>@else{{ $s->user?->name }}@endif
@if($s->user?->national_id)<br><small class="text-muted" dir="ltr">{{ $s->user->national_id }}</small>@endif</td>
<td>{{ $s->user?->parents->pluck('name')->implode('، ') ?: '—' }}</td>
<td>{{ $s->package?->name_ar }}</td>
<td>{{ $s->starts_at?->format('Y-m-d') ?? '—' }} / {{ $s->ends_at?->format('Y-m-d') ?? '—' }}</td>
<td>{{ __('hub_admin.sub_status_'.$s->status) }}</td>
<td>@if($acc){{ __('hub_admin.status_'.$acc->status) }}<br><small class="text-muted">{{ __('hub_overview.last_sync') }}: {{ $acc->synced_at?->format('Y-m-d H:i') ?? '—' }}</small>@else{{ __('hub_overview.no_account') }}@endif</td></tr>
@empty<tr><td colspan="6">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $subs->links() }}

@else
<form method="GET" class="mb-3"><div class="form-row align-items-end">
<div class="col-md-2 col-6 mb-2"><label for="linked">{{ __('hub_overview.link_state') }}</label><select class="form-control" id="linked" name="linked"><option value="">{{ __('hub_admin.all') }}</option>
<option value="1" @selected(request('linked') === '1')>{{ __('hub_overview.linked') }}</option><option value="0" @selected(request('linked') === '0')>{{ __('hub_overview.unlinked') }}</option></select></div>
<div class="col-md-4 col-12 mb-2"><label for="q">{{ __('hub_overview.search') }}</label><input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="{{ __('hub_overview.search_hint') }}"></div>
<div class="col-md-1 col-6 mb-2"><button class="btn btn-primary">{{ __('hub_admin.filter') }}</button></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['name', 'national_id', 'external_id', 'link_state', 'last_sync'] as $h)<th scope="col">{{ __('hub_overview.'.$h) }}</th>@endforeach<th scope="col"></th>
</tr></thead><tbody>
@forelse($students as $a)
<tr><td>{{ $a->name ?? '—' }}</td><td dir="ltr" class="text-start">{{ $a->national_id ?? '—' }}</td><td dir="ltr" class="text-start">{{ $a->external_id ?? '—' }}</td>
<td>@if($a->user)<a href="{{ route('admin.hub.people.show', $a->user_id) }}">{{ $a->user->name }}</a>@else{{ __('hub_overview.unlinked') }}@endif</td>
<td>{{ $a->synced_at?->format('Y-m-d H:i') ?? '—' }}</td>
<td><a href="{{ route('admin.hub.accounts.link.form', $a->id) }}">{{ __('hub_directory.review') }}</a></td></tr>
@empty<tr><td colspan="6">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $students->links() }}
<a href="{{ route('admin.hub.directory.index', ['platform_id' => $platform->id]) }}">{{ __('hub_overview.open_directory') }}</a>
@endif
</div></div>
@endsection
