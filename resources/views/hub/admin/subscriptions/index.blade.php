@extends('layouts.app')
@section('title', __('hub_admin.subscriptions'))
@section('page-title', __('hub_admin.subscriptions'))
@section('content')
<div class="card"><div class="card-body">
<a class="btn btn-primary mb-3" href="{{ route('admin.hub.subscriptions.grant') }}"><x-svg-icon name="plus-lg" /> {{ __('hub_admin.grant_package') }}</a>
<form method="GET" class="mb-3"><div class="form-row align-items-end">
<div class="col-md-3 col-6 mb-2"><label for="package_id">{{ __('hub_admin.package') }}</label><select class="form-control" id="package_id" name="package_id"><option value="">{{ __('hub_admin.all') }}</option>
@foreach($packages as $p)<option value="{{ $p->id }}" @selected(request('package_id') == $p->id)>{{ $p->name_ar }}</option>@endforeach</select></div>
<div class="col-md-2 col-6 mb-2"><label for="status">{{ __('hub_admin.status') }}</label><select class="form-control" id="status" name="status"><option value="">{{ __('hub_admin.all') }}</option>
@foreach(['pending', 'active', 'expired', 'cancelled', 'refunded'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('hub_admin.sub_status_'.$s) }}</option>@endforeach</select></div>
<div class="col-md-2 col-6 mb-2"><label for="source">{{ __('hub_admin.source') }}</label><select class="form-control" id="source" name="source"><option value="">{{ __('hub_admin.all') }}</option>
@foreach(['purchase', 'grant'] as $s)<option value="{{ $s }}" @selected(request('source') === $s)>{{ __('hub_admin.source_'.$s) }}</option>@endforeach</select></div>
<div class="col-md-2 col-6 mb-2"><label for="from">{{ __('hub_admin.from') }}</label><input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}"></div>
<div class="col-md-2 col-6 mb-2"><label for="to">{{ __('hub_admin.to') }}</label><input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}"></div>
<div class="col-md-1 col-6 mb-2"><button class="btn btn-primary">{{ __('hub_admin.filter') }}</button></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['person', 'package', 'source', 'status', 'starts_at', 'ends_at', 'price_paid'] as $h)<th scope="col">{{ __('hub_admin.'.$h) }}</th>@endforeach<th scope="col"></th>
</tr></thead><tbody>
@forelse($subscriptions as $s)
<tr><td>@if($s->user)<a href="{{ route('admin.hub.people.show', $s->user_id) }}">{{ $s->user->name }}</a>@endif</td>
<td>{{ $s->package?->name_ar }}</td><td>{{ __('hub_admin.source_'.$s->source) }}</td><td>{{ __('hub_admin.sub_status_'.$s->status) }}</td>
<td>{{ $s->starts_at?->format('Y-m-d') ?? '—' }}</td><td>{{ $s->ends_at?->format('Y-m-d') ?? '—' }}</td><td>{{ $s->price }}</td>
<td>@if(in_array($s->status, ['pending', 'active']))<form method="POST" action="{{ route('admin.hub.subscriptions.cancel', $s->id) }}">@csrf<button class="btn btn-link text-danger p-0">{{ __('hub_admin.cancel') }}</button></form>@endif</td></tr>
@empty<tr><td colspan="8">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $subscriptions->links() }}
</div></div>
@endsection
