@extends('layouts.app')
@section('title', __('points_manage.title'))
@section('page-title', __('points_manage.title'))
@section('content')
<div class="card"><div class="card-body">
@include('points.manage._nav')
<ul class="nav nav-tabs mb-3">
@foreach(['student', 'teacher', 'vice'] as $t)
<li class="nav-item"><a class="nav-link {{ $type === $t ? 'active' : '' }}" href="{{ route('manage.points.index', ['type' => $t]) }}">{{ __('points_manage.types.'.$t) }}</a></li>
@endforeach
</ul>
<form method="GET" class="mb-3"><input type="hidden" name="type" value="{{ $type }}"><div class="form-row align-items-end">
@if($type === 'student')
<div class="col-md-3 col-6 mb-2"><label for="section_id">{{ __('points_manage.grade') }}</label>
<select class="form-control" id="section_id" name="section_id" onchange="this.form.submit()"><option value="">{{ __('points_manage.all') }}</option>
@foreach($sections as $s)<option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-3 col-6 mb-2"><label for="class_id">{{ __('points_manage.class') }}</label>
<select class="form-control" id="class_id" name="class_id"><option value="">{{ __('points_manage.all') }}</option>
@foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}{{ $c->division ? ' - '.$c->division : '' }}</option>@endforeach</select></div>
@endif
<div class="col-md-3 col-6 mb-2"><label for="name">{{ __('points_manage.name') }}</label><input class="form-control" id="name" name="name" value="{{ request('name') }}" placeholder="{{ __('points_manage.search_name') }}"></div>
<div class="col-md-3 col-6 mb-2"><button class="btn btn-primary">{{ __('points_manage.filter') }}</button> <a class="btn btn-light" href="{{ route('manage.points.index', ['type' => $type]) }}">{{ __('points_manage.reset') }}</a></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('points_manage.name') }}</th><th scope="col">{{ __('points_manage.balance') }}</th><th scope="col">{{ __('points_manage.month_points') }}</th>
@if($type !== 'vice')<th scope="col">{{ __('points_manage.behaviour_balance') }}</th>@endif
<th scope="col"></th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->name }}</td><td>{{ (int) $row->balance }}</td><td>{{ (int) $row->month_points }}</td>
@if($type !== 'vice')<td class="text-muted">{{ (int) ($behaviour[$row->id] ?? 0) }}</td>@endif
<td><a class="btn btn-sm btn-light" href="{{ route('manage.points.show', $row->id) }}">{{ __('points_manage.details') }}</a></td></tr>
@empty<tr><td colspan="5">{{ __('points_manage.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}
</div></div>
@endsection
