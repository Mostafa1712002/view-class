@extends('layouts.app')
@section('title', __('special_programs/groups.groups_title'))
@section('page-title', $group->name.' — '.\App\Modules\SpecialPrograms\Support\Programs::label($program))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if(session('warning'))<div class="alert alert-warning" role="alert">{{ session('warning') }}</div>@endif
<dl>
<dt>{{ __('special_programs/groups.teacher') }}</dt><dd>{{ $teacher?->name ?? __('special_programs/groups.no_teacher') }}</dd>
<dt>{{ __('special_programs/groups.target_level') }}</dt><dd>{{ $group->target_level ? __('special_programs.levels.'.$group->target_level) : __('special_programs/groups.no_level') }}</dd>
@if($group->notes)<dt>{{ __('special_programs/groups.notes') }}</dt><dd style="white-space: pre-wrap">{{ $group->notes }}</dd>@endif
</dl>
<a class="btn btn-primary" href="{{ route('manage.special-programs.groups.edit', [$program, $group->id]) }}">{{ __('special_programs.buttons.edit') }}</a>
<a class="btn btn-light" href="{{ route('manage.special-programs.groups.index', $program) }}">{{ __('special_programs.buttons.back') }}</a>
<form method="POST" class="d-inline" action="{{ route('manage.special-programs.groups.destroy', [$program, $group->id]) }}" onsubmit="return confirm('{{ __('special_programs/groups.confirm_delete') }}')">@csrf @method('DELETE')<button class="btn btn-danger">{{ __('special_programs.buttons.delete') }}</button></form>

<h2 class="h5 mt-4">{{ __('special_programs/groups.members') }} ({{ $members->count() }})</h2>
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('special_programs/groups.student') }}</th><th scope="col">{{ __('special_programs/groups.current_level') }}</th>
<th scope="col">{{ __('special_programs/groups.last_result') }}</th><th scope="col">{{ __('special_programs/groups.actions') }}</th>
</tr></thead><tbody>
@forelse($members as $m)
@php($st = $standing[$m->student_id] ?? null)
<tr><td>{{ $m->name }}</td>
<td>@if($st)<span class="badge badge-{{ $st['color'] }}">{{ $st['name'] }}</span>@else — @endif</td>
<td>{{ $st ? number_format($st['pct'], 1).'%' : __('special_programs/groups.no_result') }}</td>
<td><form method="POST" action="{{ route('manage.special-programs.groups.members.destroy', [$program, $group->id, $m->student_id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-light">{{ __('special_programs/groups.remove') }}</button></form></td></tr>
@empty<tr><td colspan="4">{{ __('special_programs/groups.no_members') }}</td></tr>@endforelse
</tbody></table></div>

<h2 class="h5 mt-4">{{ __('special_programs/groups.add_members') }}</h2>
<p class="text-muted">{{ __('special_programs/groups.add_members_hint') }}</p>
<form method="GET" class="form-row align-items-end mb-3">
<div class="col-md-4 col-6"><label for="level">{{ __('special_programs/groups.filter_level') }}</label>
<select class="form-control" id="level" name="level" onchange="this.form.submit()"><option value="">{{ __('special_programs/groups.all_levels') }}</option>
@foreach($bands as $band)<option value="{{ $band['key'] }}" @selected($level === $band['key'])>{{ $band['name'] }} ({{ $band['min'] }}–{{ $band['max'] }}%)</option>@endforeach
<option value="none" @selected($level === 'none')>{{ __('special_programs/groups.no_result') }}</option>
</select></div></form>
<form method="POST" action="{{ route('manage.special-programs.groups.members.store', [$program, $group->id]) }}">@csrf
<div class="table-responsive"><table class="table"><thead><tr><th scope="col"></th>
<th scope="col">{{ __('special_programs/groups.student') }}</th><th scope="col">{{ __('special_programs/groups.current_level') }}</th><th scope="col">{{ __('special_programs/groups.current_group') }}</th>
</tr></thead><tbody>
@forelse($candidates as $c)
@php($st = $standing[$c->student_id] ?? null)
<tr><td><input type="checkbox" name="student_ids[]" value="{{ $c->student_id }}" aria-label="{{ $c->name }}"></td>
<td>{{ $c->name }}</td>
<td>@if($st)<span class="badge badge-{{ $st['color'] }}">{{ $st['name'] }}</span> {{ number_format($st['pct'], 1) }}%@else — @endif</td>
<td>{{ $groupNames[$c->group_id] ?? '—' }}</td></tr>
@empty<tr><td colspan="4">{{ __('special_programs/groups.no_candidates') }}</td></tr>@endforelse
</tbody></table></div>
@if($candidates->isNotEmpty())<button class="btn btn-primary">{{ __('special_programs/groups.add_selected') }}</button>@endif
</form>
</div></div>
@endsection
