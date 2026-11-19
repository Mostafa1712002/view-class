@extends('layouts.app')
@section('title', __('special_programs/groups.teachers_title'))
@section('page-title', __('special_programs/groups.teachers_title').' — '.\App\Modules\SpecialPrograms\Support\Programs::label($program))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($needsSchool)<div class="alert alert-warning" role="alert">{{ __('special_programs/groups.pick_school') }}</div>@endif
<p class="text-muted">{{ __('special_programs/groups.teachers_hint') }}</p>
@unless($needsSchool)
<form method="POST" action="{{ route('manage.special-programs.teachers.store', $program) }}" class="form-row align-items-end mb-4">@csrf
<div class="col-md-4 col-12 mb-2"><label for="teacher_id">{{ __('special_programs/groups.teacher') }}</label>
<select class="form-control" id="teacher_id" name="teacher_id" required><option value="">{{ __('special_programs/groups.choose_teacher') }}</option>
@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>@endforeach
</select></div>
<div class="col-md-4 col-12 mb-2"><label for="sp_subject_id">{{ __('special_programs/groups.subject') }}</label>
<select class="form-control" id="sp_subject_id" name="sp_subject_id"><option value="">{{ __('special_programs/groups.all_subjects') }}</option>
@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(old('sp_subject_id') == $subject->id)>{{ $subject->name_ar }}</option>@endforeach
</select></div>
<div class="col-md-4 col-12 mb-2"><button class="btn btn-primary"><x-svg-icon name="plus-lg" /> {{ __('special_programs/groups.assign_teacher') }}</button></div>
</form>
@endunless
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('special_programs/groups.teacher') }}</th><th scope="col">{{ __('special_programs/groups.subject') }}</th><th scope="col">{{ __('special_programs/groups.actions') }}</th>
</tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $names[$row->teacher_id] ?? '—' }}</td>
<td>{{ $row->sp_subject_id ? ($subjects->firstWhere('id', $row->sp_subject_id)?->name_ar ?? '—') : __('special_programs/groups.all_subjects') }}</td>
<td><form method="POST" action="{{ route('manage.special-programs.teachers.destroy', [$program, $row->id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-light">{{ __('special_programs/groups.remove') }}</button></form></td></tr>
@empty<tr><td colspan="3">{{ __('special_programs/groups.empty_teachers') }}</td></tr>@endforelse
</tbody></table></div>
<a class="btn btn-light" href="{{ route('manage.special-programs.groups.index', $program) }}">{{ __('special_programs/groups.groups_link') }}</a>
</div></div>
@endsection
