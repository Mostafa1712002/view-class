@extends('layouts.app')
@section('title', __('hub_my.title'))
@section('page-title', __('hub_my.title'))
@section('content')
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
<div class="card mb-3"><div class="card-body">
<h2 class="h5">{{ __('hub_my.available_packages') }}</h2>
<div class="row">
@forelse($packages as $p)
<div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body">
<h3 class="h6">{{ $p->name_ar }}</h3>
@if($p->description_ar)<p class="text-muted">{{ $p->description_ar }}</p>@endif
<p class="mb-1"><strong>{{ __('hub_my.price', ['price' => $p->price]) }}</strong></p>
<p class="text-muted">{{ __('hub_my.days', ['days' => $p->duration_days]) }}</p>
<a class="btn btn-primary" href="{{ route('my.packages.buy', $p->id) }}">{{ __('hub_my.buy') }}</a>
</div></div></div>
@empty<p class="col-12">{{ __('hub_my.no_packages') }}</p>@endforelse
</div></div></div>

@if($isParent)
<div class="card mb-3"><div class="card-body">
<a class="btn btn-light mb-3" href="{{ route('my.packages.children.create') }}"><x-svg-icon name="plus-lg" /> {{ __('hub_my.add_child') }}</a>
@if($people->isEmpty())<p>{{ __('hub_my.no_children') }}</p>@endif
@if($ownAccounts->isNotEmpty())<h2 class="h5">{{ __('hub_my.my_accounts') }}</h2>@include('hub.my._accounts', ['accounts' => $ownAccounts, 'canSee' => true])@endif
</div></div>
@endif

@foreach($people as $row)
<div class="card mb-3"><div class="card-body">
<h2 class="h5">{{ $isParent ? $row['user']->name : __('hub_my.my_subscriptions') }}</h2>
@if(count($row['subscriptions']))
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('hub_my.package') }}</th><th scope="col">{{ __('hub_my.status') }}</th><th scope="col">{{ __('hub_my.starts_at') }}</th><th scope="col">{{ __('hub_my.ends_at') }}</th>
</tr></thead><tbody>
@foreach($row['subscriptions'] as $s)
<tr><td>{{ $s->package?->name_ar }}</td><td>{{ __('hub_my.sub_status_'.$s->status) }}</td><td>{{ $s->starts_at?->format('Y-m-d') ?? '—' }}</td><td>{{ $s->ends_at?->format('Y-m-d') ?? '—' }}</td></tr>
@endforeach
</tbody></table></div>
@else<p>{{ __('hub_my.no_subscriptions') }}</p>@endif
@include('hub.my._accounts', ['accounts' => $row['accounts']->where('role', 'student'), 'canSee' => true])
</div></div>
@endforeach
@endsection
