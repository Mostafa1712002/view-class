@extends('layouts.app')
@section('title', __('points_manage.honour_title'))
@section('page-title', __('points_manage.honour_title'))
@section('content')
<div class="card"><div class="card-body">
@include('points.manage._nav')
@unless($schoolId)<div class="alert alert-warning" role="alert">{{ __('points_manage.pick_school') }}</div>@endunless
<form method="GET" class="mb-3"><div class="form-row align-items-end">
<div class="col-md-3 col-6 mb-2"><label for="month">{{ __('points_manage.month') }}</label><input type="month" class="form-control" id="month" name="month" value="{{ $month }}"></div>
<div class="col-md-3 col-6 mb-2"><label for="type">{{ __('points_manage.rule') }}</label><select class="form-control" id="type" name="type">
@foreach(['student', 'teacher', 'vice'] as $t)<option value="{{ $t }}" @selected($type === $t)>{{ __('points_manage.types.'.$t) }}</option>@endforeach</select></div>
<div class="col-md-3 col-6 mb-2"><label for="section_id">{{ __('points_manage.grade') }}</label><select class="form-control" id="section_id" name="section_id"><option value="">{{ __('points_manage.all') }}</option>
@foreach($sections as $s)<option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-3 col-6 mb-2"><button class="btn btn-primary">{{ __('points_manage.filter') }}</button></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr><th scope="col">{{ __('points_manage.rank') }}</th><th scope="col">{{ __('points_manage.name') }}</th><th scope="col">{{ __('points_manage.points') }}</th></tr></thead><tbody>
@forelse($list as $i => $row)<tr><td>{{ $i + 1 }}</td><td>{{ $row->name }}</td><td>{{ (int) $row->points }}</td></tr>@empty<tr><td colspan="3">{{ __('points_manage.empty') }}</td></tr>@endforelse
</tbody></table></div>
</div></div>
@endsection
