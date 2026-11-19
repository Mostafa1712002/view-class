@extends('layouts.app')
@section('title', __('points_manage.rewards_title'))
@section('page-title', __('points_manage.rewards_title'))
@section('content')
<div class="card"><div class="card-body">
@include('points.manage._nav')
<ul class="nav nav-tabs mb-3">
<li class="nav-item"><a class="nav-link {{ ! $status ? 'active' : '' }}" href="{{ route('manage.points.rewards.index') }}">{{ __('points_manage.all') }}</a></li>
@foreach(['earned', 'delivered'] as $st)<li class="nav-item"><a class="nav-link {{ $status === $st ? 'active' : '' }}" href="{{ route('manage.points.rewards.index', ['status' => $st]) }}">{{ __('points_manage.statuses.'.$st) }}</a></li>@endforeach
</ul>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['student', 'period', 'points_at_award', 'status', 'delivered_at'] as $c)<th scope="col">{{ __('points_manage.'.$c) }}</th>@endforeach<th scope="col"></th></tr></thead><tbody>
@forelse($rewards as $r)
<tr><td>{{ $r->user?->name }}</td><td>{{ $r->period?->name }}</td><td>{{ $r->points_at_award }}</td><td>{{ __('points_manage.statuses.'.$r->status) }}</td><td>{{ $r->delivered_at?->format('Y-m-d H:i') ?? '—' }}</td>
<td>@if($r->status === 'earned')<form method="POST" action="{{ route('manage.points.rewards.deliver', $r->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-primary">{{ __('points_manage.deliver') }}</button></form>@endif</td></tr>
@empty<tr><td colspan="6">{{ __('points_manage.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $rewards->links() }}
</div></div>
@endsection
