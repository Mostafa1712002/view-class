@extends('layouts.app')
@section('title', __('special_programs/groups.groups_title'))
@section('page-title', __('special_programs/groups.groups_title').' — '.\App\Modules\SpecialPrograms\Support\Programs::label($program))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($needsSchool)<div class="alert alert-warning" role="alert">{{ __('special_programs/groups.pick_school') }}</div>@endif
@unless($needsSchool)<a class="btn btn-primary mb-3" href="{{ route('manage.special-programs.groups.create', $program) }}"><x-svg-icon name="plus-lg" /> {{ __('special_programs/groups.new_group') }}</a>@endunless
<a class="btn btn-light mb-3" href="{{ route('manage.special-programs.teachers.index', $program) }}"><x-svg-icon name="person-badge" /> {{ __('special_programs/groups.teachers_link') }}</a>
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('special_programs/groups.name') }}</th><th scope="col">{{ __('special_programs/groups.teacher') }}</th>
<th scope="col">{{ __('special_programs/groups.target_level') }}</th><th scope="col">{{ __('special_programs/groups.members_count') }}</th>
</tr></thead><tbody>
@forelse($groups as $group)
<tr>
<td><a href="{{ route('manage.special-programs.groups.show', [$program, $group->id]) }}">{{ $group->name }}</a></td>
<td>{{ $teacherNames[$group->teacher_id] ?? __('special_programs/groups.no_teacher') }}</td>
<td>{{ $group->target_level ? __('special_programs.levels.'.$group->target_level) : __('special_programs/groups.no_level') }}</td>
<td>{{ $group->enrollments_count }}</td>
</tr>@empty<tr><td colspan="4">{{ __('special_programs/groups.empty_groups') }}</td></tr>@endforelse
</tbody></table></div>
</div></div>
@endsection
