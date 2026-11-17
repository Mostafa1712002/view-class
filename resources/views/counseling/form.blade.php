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
@if($case->exists)
<label for="student_id">{{ __('counseling.student') }}</label>
<select class="form-control" name="student_id" id="student_id" required>
<option value="">{{ __('counseling.choose') }}</option>
@foreach($students as $person)<option value="{{ $person->id }}" @selected(old('student_id', $case->student_id) == $person->id)>{{ $person->name }}</option>@endforeach
</select>
@else
<div class="form-row">
<div class="col-md-6"><label for="filter_section_id">{{ __('counseling.grade') }}</label>
<select class="form-control" name="filter_section_id" id="filter_section_id">
<option value="">{{ __('counseling.all_grades') }}</option>
@foreach($sections as $section)<option data-school="{{ $section->school_id }}" value="{{ $section->id }}" @selected(old('filter_section_id') == $section->id)>{{ $section->name }}</option>@endforeach
</select></div>
<div class="col-md-6"><label for="filter_class_id">{{ __('counseling.class') }}</label>
<select class="form-control" name="filter_class_id" id="filter_class_id">
<option value="">{{ __('counseling.all_classes') }}</option>
@foreach($classes as $class)<option data-section="{{ $class->section_id }}" value="{{ $class->id }}" @selected(old('filter_class_id') == $class->id)>{{ $class->name }}{{ $class->division ? ' - '.$class->division : '' }}</option>@endforeach
</select></div>
</div>
<label for="student_ids">{{ __('counseling.students') }}</label>
<select class="form-control" name="student_ids[]" id="student_ids" multiple data-placeholder="{{ __('counseling.pick_students') }}"></select>
<div class="custom-control custom-checkbox mt-1">
<input type="checkbox" class="custom-control-input" name="all_students" id="all_students" value="1" @checked(old('all_students'))>
<label class="custom-control-label" for="all_students">{{ __('counseling.all_students') }} (<span id="students_count">0</span>)</label>
</div>
@endif
<label for="counselor_id">{{ __('counseling.counselor') }}</label>
<select class="form-control" name="counselor_id" id="counselor_id" required>
<option value="">{{ __('counseling.choose') }}</option>
@foreach($counselors as $person)
@if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin() || $person->id === auth()->id())
<option data-school="{{ $person->school_id }}" value="{{ $person->id }}" @selected(old('counselor_id', $case->counselor_id) == $person->id)>{{ $person->name }}</option>
@endif
@endforeach</select>
@foreach(['category' => 'categories', 'status' => 'statuses'] as $field => $group)
<label for="{{ $field }}">{{ __('counseling.'.$field) }}</label><select class="form-control" id="{{ $field }}" name="{{ $field }}" required>
@foreach(__('counseling.'.$group) as $value => $label)<option value="{{ $value }}" @selected(old($field, $case->$field) === $value)>{{ $label }}</option>@endforeach
</select>@endforeach
<label for="title">{{ __('counseling.case_title') }}</label><input class="form-control" id="title" name="title" maxlength="255" required value="{{ old('title', $case->title) }}">
<label for="summary">{{ __('counseling.summary') }}</label><textarea class="form-control" id="summary" name="summary" rows="5" required>{{ old('summary', $case->summary) }}</textarea>
<button class="btn btn-primary mt-3">{{ __('counseling.save') }}</button>
<a href="{{ route('manage.counseling.index') }}">{{ __('counseling.back') }}</a>
</form></div></div>
@unless($case->exists)
<style>#student_ids + .select2 .select2-selection--multiple { max-height: 12rem; overflow-y: auto; }</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const students = {{ Js::from($studentOptions) }};
    const oldIds = new Set({{ Js::from(array_map('strval', (array) old('student_ids', []))) }});
    const el = id => document.getElementById(id);
    const school = el('school_id'), grade = el('filter_section_id'), klass = el('filter_class_id');
    const picker = el('student_ids'), all = el('all_students'), count = el('students_count');
    const gradeSchool = id => grade.querySelector('option[value="' + id + '"]')?.dataset.school;
    const narrow = (select, keep) => Array.from(select.options).forEach(option => {
        const excluded = !!option.value && !keep(option);
        option.hidden = excluded;
        option.disabled = excluded;
        if (excluded && option.selected) select.value = '';
    });
    const render = () => {
        const schoolId = school ? school.value : null;
        if (school) {
            narrow(el('counselor_id'), o => o.dataset.school === schoolId);
            narrow(grade, o => o.dataset.school === schoolId);
        }
        narrow(klass, o => (!school || gradeSchool(o.dataset.section) === schoolId) && (!grade.value || o.dataset.section === grade.value));
        const inSchool = s => !school || String(s.school) === schoolId;
        const matches = students.filter(s => inSchool(s)
            && (!grade.value || s.sections.includes(+grade.value))
            && (!klass.value || s.classes.includes(+klass.value)));
        // Students picked under another grade/class stay picked while the filter changes.
        const picked = new Set(Array.from(picker.selectedOptions, o => o.value).concat(Array.from(oldIds)));
        oldIds.clear();
        const shown = new Set(matches.map(s => String(s.id)));
        const list = all.checked ? matches : matches.concat(students.filter(s => inSchool(s) && picked.has(String(s.id)) && !shown.has(String(s.id))));
        picker.replaceChildren(...list.map(s => new Option(s.name, s.id, false,
            all.checked ? shown.has(String(s.id)) : picked.has(String(s.id)))));
        picker.disabled = all.checked;
        picker.required = !all.checked;
        count.textContent = matches.length;
        if (window.jQuery && jQuery(picker).hasClass('select2-hidden-accessible')) jQuery(picker).trigger('change.select2');
    };
    if (window.jQuery && jQuery.fn.select2) {
        jQuery(picker).select2({ theme: 'bootstrap4', width: '100%', dir: document.documentElement.getAttribute('dir') || 'rtl',
            placeholder: picker.dataset.placeholder, closeOnSelect: false });
    }
    all.addEventListener('change', () => { if (!all.checked) Array.from(picker.options).forEach(o => { o.selected = false; }); });
    [school, grade, klass, all].forEach(control => control && control.addEventListener('change', render));
    render();
});
</script>
@endunless
@endsection
