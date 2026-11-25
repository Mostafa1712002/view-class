{{-- Grade + class filters narrow the student list (and each other). $selectedStudent = current student id. --}}
@php $selectedStudent = old('student_id', $selectedStudent ?? null); @endphp
<div class="col-12 col-md-6 form-group">
    <label for="se_section">@lang('special_education.field_grade')</label>
    <select id="se_section" class="form-control">
        <option value="">@lang('special_education.all_grades')</option>
        @foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach
    </select>
</div>
<div class="col-12 col-md-6 form-group">
    <label for="se_class">@lang('special_education.field_class')</label>
    <select id="se_class" class="form-control">
        <option value="">@lang('special_education.all_classes')</option>
        @foreach($classes as $class)<option data-section="{{ $class->section_id }}" value="{{ $class->id }}">{{ $class->name }}{{ $class->division ? ' - '.$class->division : '' }}</option>@endforeach
    </select>
</div>
<div class="col-12 col-md-6 form-group">
    <label class="required" for="se_student">@lang('special_education.field_student')</label>
    <select name="student_id" id="se_student" class="form-control @error('student_id') is-invalid @enderror" required>
        <option value="">@lang('special_education.select_student')</option>
    </select>
    @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const students = {{ Js::from($studentOptions) }};
    const keep = @json($selectedStudent ? (int) $selectedStudent : null);
    const el = id => document.getElementById(id);
    const grade = el('se_section'), klass = el('se_class'), picker = el('se_student');
    const placeholder = picker.options[0].text;
    const render = () => {
        Array.from(klass.options).forEach(o => {
            const out = !!o.value && !!grade.value && o.dataset.section !== grade.value;
            o.hidden = o.disabled = out;
            if (out && o.selected) klass.value = '';
        });
        const current = picker.value ? +picker.value : keep;
        const list = students.filter(s => (!grade.value || s.sections.includes(+grade.value))
            && (!klass.value || s.classes.includes(+klass.value)));
        picker.replaceChildren(new Option(placeholder, ''), ...list.map(s => new Option(s.name, s.id, false, s.id === current)));
    };
    [grade, klass].forEach(c => c.addEventListener('change', render));
    render();
});
</script>
