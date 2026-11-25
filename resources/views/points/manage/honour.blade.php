@extends('layouts.app')
@section('title', __('points_manage.honour_title'))
@section('page-title', __('points_manage.honour_title'))
@section('content')
<div class="card"><div class="card-body">
@include('points.manage._nav')
@unless($schoolId)<div class="alert alert-warning" role="alert">{{ __('points_manage.pick_school') }}</div>@endunless
<form method="GET" class="mb-3"><div class="form-row align-items-end">
<div class="col-md-3 col-6 mb-2"><label for="month">{{ __('points_manage.month') }}</label><input type="month" class="form-control" id="month" name="month" value="{{ $month }}"></div>
<div class="col-md-3 col-6 mb-2"><label for="type">{{ __('points_manage.category') }}</label><select class="form-control" id="type" name="type" onchange="this.form.submit()">
@foreach(['student', 'teacher', 'vice'] as $t)<option value="{{ $t }}" @selected($type === $t)>{{ __('points_manage.types.'.$t) }}</option>@endforeach</select></div>
@if($type === 'student')
<div class="col-md-3 col-6 mb-2"><label for="section_id">{{ __('points_manage.grade') }}</label><select class="form-control" id="section_id" name="section_id" onchange="this.form.submit()"><option value="">{{ __('points_manage.all') }}</option>
@foreach($sections as $s)<option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-3 col-6 mb-2"><label for="class_id">{{ __('points_manage.class') }}</label><select class="form-control" id="class_id" name="class_id"><option value="">{{ __('points_manage.all') }}</option>
@foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}{{ $c->division ? ' - '.$c->division : '' }}</option>@endforeach</select></div>
@elseif($type === 'teacher')
<div class="col-md-3 col-6 mb-2"><label for="subject_id">{{ __('points_manage.subject') }}</label><select class="form-control" id="subject_id" name="subject_id"><option value="">{{ __('points_manage.all') }}</option>
@foreach($subjects as $sub)<option value="{{ $sub->id }}" @selected(request('subject_id') == $sub->id)>{{ $sub->name }}</option>@endforeach</select></div>
@endif
<div class="col-md-3 col-6 mb-2"><button class="btn btn-primary">{{ __('points_manage.filter') }}</button></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr><th scope="col">{{ __('points_manage.rank') }}</th><th scope="col">{{ __('points_manage.name') }}</th>
@if($type === 'student')<th scope="col">{{ __('points_manage.grade_col') }}</th><th scope="col">{{ __('points_manage.class') }}</th>@elseif($type === 'teacher')<th scope="col">{{ __('points_manage.subject') }}</th>@endif
<th scope="col">{{ __('points_manage.points') }}</th></tr></thead><tbody>
@forelse($list as $i => $row)<tr><td>{{ $i + 1 }}</td><td>{{ $row->name }}</td>
@if($type === 'student')@php($place = $people->has($row->user_id) ? \App\Modules\Gamification\Support\ClassStudents::placement($people[$row->user_id]) : [])<td>{{ $place['grade'] ?? '—' }}</td><td>{{ $place['class'] ?? '—' }}</td>@elseif($type === 'teacher')<td>{{ $people->has($row->user_id) ? ($people[$row->user_id]->subjects->pluck('name')->implode('، ') ?: '—') : '—' }}</td>@endif
<td>{{ (int) $row->points }}</td></tr>@empty<tr><td colspan="5">{{ __('points_manage.empty') }}</td></tr>@endforelse
</tbody></table></div>
</div></div>
@endsection
