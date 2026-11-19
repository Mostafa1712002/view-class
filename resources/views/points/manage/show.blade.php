@extends('layouts.app')
@section('title', $person->name)
@section('page-title', $person->name)
@section('content')
<div class="card"><div class="card-body">
@include('points.manage._nav')
<a class="btn btn-light mb-3" href="{{ route('manage.points.index') }}">{{ __('points_manage.back') }}</a>
<div class="d-flex flex-wrap mb-3">
<span class="badge badge-primary m-1">{{ __('points_manage.balance') }}: {{ $balance }}</span>
@if($behaviourTotal !== null)<span class="badge badge-light m-1">{{ __('points_manage.behaviour_balance') }}: {{ $behaviourTotal }}</span>@endif
</div>
@if($canAdjust)
<h2 class="h5">{{ __('points_manage.adjust_title') }}</h2>
<form method="POST" action="{{ route('manage.points.adjust', $person->id) }}" class="mb-4">@csrf
<p class="text-muted">{{ __('points_manage.adjust_hint') }}</p>
<div class="form-row align-items-end">
<div class="col-md-2 col-4 mb-2"><label for="points">{{ __('points_manage.points') }}</label><input type="number" class="form-control" id="points" name="points" value="{{ old('points') }}" required></div>
<div class="col-md-6 col-8 mb-2"><label for="reason">{{ __('points_manage.reason') }}</label><input class="form-control" id="reason" name="reason" maxlength="255" value="{{ old('reason') }}" required></div>
<div class="col-md-3 mb-2"><button class="btn btn-primary">{{ __('points_manage.adjust_submit') }}</button></div>
</div></form>
@elseif(auth()->id() === $person->id)
<p class="text-muted">{{ __('points_manage.cannot_adjust_self') }}</p>
@endif
<h2 class="h5">{{ __('points_manage.ledger') }}</h2>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['date', 'rule', 'source', 'points', 'reason'] as $c)<th scope="col">{{ __('points_manage.'.$c) }}</th>@endforeach
</tr></thead><tbody>
@forelse($history as $t)
<tr><td>{{ $t->created_at?->format('Y-m-d H:i') }}</td>
<td>{{ $t->rule_key === 'manual' ? __('points.kinds.manual') : \App\Modules\Gamification\Support\RuleCatalog::label($t->rule_key) }}</td>
<td>{{ __('points.sources.'.$t->source_type) }}</td><td dir="ltr" class="text-right">{{ $t->points > 0 ? '+' : '' }}{{ $t->points }}</td><td>{{ $t->reason }}</td></tr>
@empty<tr><td colspan="5">{{ __('points_manage.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $history->links() }}
</div></div>
@endsection
