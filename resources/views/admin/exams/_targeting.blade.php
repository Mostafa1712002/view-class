@php
    $mode = old('target_mode', isset($exam) && $exam->targetStudents->isNotEmpty() ? 'students' : 'class');
    $picked = array_map('strval', (array) old('student_ids', isset($exam) ? $exam->targetStudents->pluck('id')->all() : []));
@endphp
<div class="mb-3" id="exam-targeting">
    <label class="form-label d-block">توجيه الاختبار</label>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="target_mode" id="tm_class" value="class" @checked($mode !== 'students')>
        <label class="form-check-label" for="tm_class">كل طلاب الصف</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="target_mode" id="tm_students" value="students" @checked($mode === 'students')>
        <label class="form-check-label" for="tm_students">طلاب محددين</label>
    </div>
    <div id="tm_picker_wrap" class="mt-2" @if($mode !== 'students') style="display:none" @endif>
        <select class="form-select @error('student_ids') is-invalid @enderror" name="student_ids[]" id="student_ids" multiple data-placeholder="اختر الطلاب"></select>
        @error('student_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <small class="text-muted">الطلاب المعروضون هم طلاب الصف المختار فقط.</small>
    </div>
</div>
<style>#student_ids + .select2 .select2-selection--multiple { max-height: 12rem; overflow-y: auto; }</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const students = {{ Illuminate\Support\Js::from($studentOptions) }};
    const picked = new Set({{ Illuminate\Support\Js::from($picked) }});
    const picker = document.getElementById('student_ids');
    const klass = document.querySelector('select[name="class_id"]');
    const wrap = document.getElementById('tm_picker_wrap');
    const modes = document.querySelectorAll('input[name="target_mode"]');
    const render = () => {
        Array.from(picker.selectedOptions).forEach(o => picked.add(o.value));
        const inClass = students.filter(s => klass.value && s.classes.includes(+klass.value));
        const keep = new Set(inClass.map(s => String(s.id)));
        picker.replaceChildren(...inClass.map(s => new Option(s.name, s.id, false, picked.has(String(s.id)))));
        picked.forEach(id => { if (!keep.has(id)) picked.delete(id); });
        if (window.jQuery && jQuery(picker).hasClass('select2-hidden-accessible')) jQuery(picker).trigger('change.select2');
    };
    const toggle = () => { wrap.style.display = document.getElementById('tm_students').checked ? '' : 'none'; };
    if (window.jQuery && jQuery.fn.select2) {
        jQuery(picker).select2({ theme: 'bootstrap4', width: '100%', dir: 'rtl', placeholder: picker.dataset.placeholder, closeOnSelect: false });
    }
    klass.addEventListener('change', render);
    modes.forEach(m => m.addEventListener('change', toggle));
    render();
});
</script>
