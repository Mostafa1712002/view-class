@extends('layouts.app')
@section('title', __('special_programs/groups.groups_title'))
@section('page-title', __('special_programs/groups.'.($group->exists ? 'edit_group' : 'new_group')).' — '.\App\Modules\SpecialPrograms\Support\Programs::label($program))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($needsSchool)<div class="alert alert-warning" role="alert">{{ __('special_programs/groups.pick_school') }}</div>@endif
<form method="POST" action="{{ $group->exists ? route('manage.special-programs.groups.update', [$program, $group->id]) : route('manage.special-programs.groups.store', $program) }}">
@csrf
@if($group->exists) @method('PUT') @endif
<div class="form-group"><label for="name">{{ __('special_programs/groups.name') }}</label>
<input class="form-control" id="name" name="name" required maxlength="255" value="{{ old('name', $group->name) }}"></div>
<div class="form-group"><label for="teacher_id">{{ __('special_programs/groups.teacher') }}</label>
<select class="form-control" id="teacher_id" name="teacher_id"><option value="">{{ __('special_programs/groups.no_teacher') }}</option>
@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $group->teacher_id) == $teacher->id)>{{ $teacher->name }}</option>@endforeach
</select></div>
<div class="form-group"><label for="target_level">{{ __('special_programs/groups.target_level') }}</label>
<select class="form-control" id="target_level" name="target_level"><option value="">{{ __('special_programs/groups.no_level') }}</option>
@foreach(__('special_programs.levels') as $key => $label)<option value="{{ $key }}" @selected(old('target_level', $group->target_level) === $key)>{{ $label }}</option>@endforeach
</select></div>
<div class="form-group"><label for="notes">{{ __('special_programs/groups.notes') }}</label>
<textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes', $group->notes) }}</textarea></div>
<button class="btn btn-primary">{{ __('special_programs.buttons.save') }}</button>
<a class="btn btn-light" href="{{ route('manage.special-programs.groups.index', $program) }}">{{ __('special_programs.buttons.cancel') }}</a>
</form>
</div></div>
@endsection
