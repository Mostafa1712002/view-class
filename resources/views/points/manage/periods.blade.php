@extends('layouts.app')
@section('title', __('points_manage.periods_title'))
@section('page-title', __('points_manage.periods_title'))
@section('content')
<div class="card"><div class="card-body">
@include('points.manage._nav')
@if($schoolId)
<form method="POST" action="{{ route('manage.points.periods.store') }}" class="mb-4">@csrf
<h2 class="h5">{{ __('points_manage.add_period') }}</h2>
@include('points.manage._period_fields', ['period' => null])
<button class="btn btn-primary">{{ __('points_manage.add_period') }}</button></form>
@else<div class="alert alert-warning" role="alert">{{ __('points_manage.pick_school') }}</div>@endif
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('points_manage.period_name') }}</th><th scope="col">{{ __('points_manage.grade') }}</th><th scope="col">{{ __('points_manage.starts_on') }}</th><th scope="col">{{ __('points_manage.ends_on') }}</th><th scope="col">{{ __('points_manage.min_points') }}</th><th scope="col"></th></tr></thead><tbody>
@forelse($periods as $p)
<tr><td>{{ $p->name }}</td><td>{{ $sections->firstWhere('id', $p->section_id)?->name ?? __('points_manage.all_grades') }}</td>
<td>{{ $p->starts_on->format('Y-m-d') }}</td><td>{{ $p->ends_on->format('Y-m-d') }}</td><td>{{ $p->min_points }}</td>
<td>@if($schoolId)
<details><summary class="btn btn-sm btn-light">{{ __('points_manage.edit') }}</summary>
<form method="POST" action="{{ route('manage.points.periods.update', $p->id) }}" class="mt-2">@csrf @method('PUT')
@include('points.manage._period_fields', ['period' => $p])
<button class="btn btn-sm btn-primary">{{ __('points_manage.save') }}</button></form></details>
<form method="POST" action="{{ route('manage.points.periods.destroy', $p->id) }}" class="d-inline" onsubmit="return confirm('{{ __('points_manage.confirm_delete') }}')">@csrf @method('DELETE')
<button class="btn btn-sm btn-outline-danger mt-1">{{ __('points_manage.delete') }}</button></form>
@endif</td></tr>
@empty<tr><td colspan="6">{{ __('points_manage.empty') }}</td></tr>@endforelse
</tbody></table></div>
</div></div>
@endsection
