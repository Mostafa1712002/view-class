@extends('layouts.app')
@section('title', __('counseling.title'))
@section('page-title', __('counseling.title'))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<h2 class="h5">{{ __('counseling.'.($case->exists ? 'edit' : 'new')) }}</h2>
<form method="POST" action="{{ $case->exists ? route('manage.counseling.update', $case->id) : route('manage.counseling.store') }}">
@csrf
@if($case->exists) @method('PUT') @endif
@if(auth()->user()->isSuperAdmin() && !$case->exists)
<label for="school_id">{{ __('counseling.school') }}</label><select class="form-control" name="school_id" id="school_id" required>
<option value="">{{ __('counseling.choose') }}</option>
@foreach($schools as $school)<option value="{{ $school->id }}" @selected(old('school_id', session('scope.school_id')) == $school->id)>{{ $school->name }}</option>@endforeach
</select>
@endif
@foreach(['student' => $students, 'counselor' => $counselors] as $field => $people)
<label for="{{ $field }}_id">{{ __('counseling.'.$field) }}</label>
<select class="form-control" name="{{ $field }}_id" id="{{ $field }}_id" required>
<option value="">{{ __('counseling.choose') }}</option>
@foreach($people as $person)
@if($field !== 'counselor' || auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin() || $person->id === auth()->id())
<option data-school="{{ $person->school_id }}" value="{{ $person->id }}" @selected(old($field.'_id', $case->{$field.'_id'}) == $person->id)>{{ $person->name }}</option>
@endif
@endforeach</select>
@endforeach
@foreach(['category' => 'categories', 'status' => 'statuses'] as $field => $group)
<label for="{{ $field }}">{{ __('counseling.'.$field) }}</label><select class="form-control" id="{{ $field }}" name="{{ $field }}" required>
@foreach(__('counseling.'.$group) as $value => $label)<option value="{{ $value }}" @selected(old($field, $case->$field) === $value)>{{ $label }}</option>@endforeach
</select>@endforeach
<label for="title">{{ __('counseling.case_title') }}</label><input class="form-control" id="title" name="title" maxlength="255" required value="{{ old('title', $case->title) }}">
<label for="summary">{{ __('counseling.summary') }}</label><textarea class="form-control" id="summary" name="summary" rows="5" required>{{ old('summary', $case->summary) }}</textarea>
<button class="btn btn-primary mt-3">{{ __('counseling.save') }}</button>
<a href="{{ route('manage.counseling.index') }}">{{ __('counseling.back') }}</a>
</form></div></div>
@if(auth()->user()->isSuperAdmin() && !$case->exists)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const school = document.getElementById('school_id');
    const filter = () => {
        ['student_id', 'counselor_id'].forEach(id => {
            const select = document.getElementById(id);
            Array.from(select.options).forEach(option => {
                const excluded = !!option.value && option.dataset.school !== school.value;
                option.hidden = excluded;
                option.disabled = excluded;
                if (excluded && option.selected) select.value = '';
            });
        });
    };
    school.addEventListener('change', filter);
    filter();
});
</script>
@endif
@endsection
