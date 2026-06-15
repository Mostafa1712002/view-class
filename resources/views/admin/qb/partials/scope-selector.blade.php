@php
    /**
     * Reusable school/grade/class/semester/week selector (#249).
     * Inputs: $tree (from QbScopeService::schoolTree), $scope (QbScopeService),
     *         $selected (assoc: school_id, grade_id, class_id, semester_id, week_id),
     *         optional $standalone (bool) to render a "compound_id/school_type" summary card.
     * Emits hidden form fields: scope_school_id, grade_id, class_id, semester_id, week_id.
     */
    $selected = $selected ?? [];
    $standalone = $standalone ?? false;
    $genderLabel = ['boys'=>'بنين','girls'=>'بنات','mixed'=>'مشترك'];
    $hasSchools = $tree['compounds']->isNotEmpty() || $tree['ungrouped']->isNotEmpty();
@endphp

<div class="qb-scope" data-scope>
    {{-- toolbar --}}
    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
        <input type="text" class="form-control form-control-sm" style="max-width:240px;" placeholder="بحث داخل المدارس" data-scope-search>
        <select class="form-select form-select-sm" style="max-width:160px;" data-scope-gender>
            <option value="">كل الأنواع</option>
            <option value="boys">بنين</option>
            <option value="girls">بنات</option>
            <option value="mixed">مشترك</option>
        </select>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-scope-clear>تفريغ الحقول</button>
    </div>

    @if(! $hasSchools)
        <div class="alert alert-light mb-0">لا توجد مدارس متاحة ضمن نطاقك.</div>
    @else
        <div data-scope-list>
            @foreach($tree['compounds'] as $group)
                <div class="compound-title">
                    <x-svg-icon name="building-fill" :size="14" /> {{ $group['compound']->name_ar }}
                </div>
                @foreach($group['schools'] as $school)
                    @include('admin.qb.partials.scope-school', ['school' => $school, 'compoundId' => $group['compound']->id, 'selected' => $selected])
                @endforeach
            @endforeach

            @if($tree['ungrouped']->isNotEmpty())
                @if($tree['compounds']->isNotEmpty())
                    <div class="compound-title"><x-svg-icon name="building" :size="14" /> مدارس غير مرتبطة بمجمع</div>
                @endif
                @foreach($tree['ungrouped'] as $school)
                    @include('admin.qb.partials.scope-school', ['school' => $school, 'compoundId' => null, 'selected' => $selected])
                @endforeach
            @endif
        </div>
    @endif

    {{-- hidden emitted values --}}
    <input type="hidden" name="compound_id"  data-scope-compound value="{{ $selected['compound_id'] ?? '' }}">
    <input type="hidden" name="scope_school_id" data-scope-school value="{{ $selected['school_id'] ?? '' }}">
    <input type="hidden" name="school_type"   data-scope-school-type value="">
    <input type="hidden" name="grade_id"      data-scope-grade value="{{ $selected['grade_id'] ?? '' }}">
    <input type="hidden" name="class_id"      data-scope-class value="{{ $selected['class_id'] ?? '' }}">
    <input type="hidden" name="semester_id"   data-scope-semester value="{{ $selected['semester_id'] ?? '' }}">
    <input type="hidden" name="week_id"       data-scope-week value="{{ $selected['week_id'] ?? '' }}">

    {{-- cascade pickers, populated by AJAX on school select --}}
    <div class="row g-3 mt-1" data-scope-cascade style="{{ ($selected['school_id'] ?? null) ? '' : 'display:none;' }}">
        <div class="col-md-3">
            <label class="form-label">الصف</label>
            <select class="form-select form-select-sm" data-cascade-grade></select>
        </div>
        <div class="col-md-3">
            <label class="form-label">الفصل</label>
            <select class="form-select form-select-sm" data-cascade-class></select>
        </div>
        <div class="col-md-3">
            <label class="form-label">الفصل الدراسي</label>
            <select class="form-select form-select-sm" data-cascade-semester></select>
        </div>
        <div class="col-md-3">
            <label class="form-label">الأسبوع</label>
            <select class="form-select form-select-sm" data-cascade-week></select>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-scope]').forEach(initScope);

    function initScope(root) {
        const schoolHidden = root.querySelector('[data-scope-school]');
        const compoundHidden = root.querySelector('[data-scope-compound]');
        const typeHidden = root.querySelector('[data-scope-school-type]');
        const gradeH = root.querySelector('[data-scope-grade]');
        const classH = root.querySelector('[data-scope-class]');
        const semH = root.querySelector('[data-scope-semester]');
        const weekH = root.querySelector('[data-scope-week]');
        const cascade = root.querySelector('[data-scope-cascade]');
        const gradeSel = root.querySelector('[data-cascade-grade]');
        const classSel = root.querySelector('[data-cascade-class]');
        const semSel = root.querySelector('[data-cascade-semester]');
        const weekSel = root.querySelector('[data-cascade-week]');
        const schoolUrl = root.dataset.schoolUrl || "{{ url('admin/qb/scope/school') }}";

        function opt(sel, items, placeholder, sel0) {
            sel.innerHTML = '<option value="">' + placeholder + '</option>';
            items.forEach(i => {
                const o = document.createElement('option');
                o.value = i.id; o.textContent = i.name;
                if (sel0 && String(sel0) === String(i.id)) o.selected = true;
                sel.appendChild(o);
            });
        }

        root.querySelectorAll('[data-scope-school-card]').forEach(card => {
            card.addEventListener('click', function () {
                root.querySelectorAll('[data-scope-school-card]').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                schoolHidden.value = card.dataset.schoolId;
                compoundHidden.value = card.dataset.compoundId || '';
                typeHidden.value = card.dataset.schoolType || '';
                loadSchool(card.dataset.schoolId);
            });
        });

        function loadSchool(schoolId, keep) {
            fetch(schoolUrl + '/' + schoolId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => {
                    cascade.style.display = '';
                    opt(gradeSel, d.grades, 'كل الصفوف', keep ? gradeH.value : '');
                    opt(classSel, d.classes, 'كل الفصول', keep ? classH.value : '');
                    opt(semSel, d.semesters, 'كل الفصول الدراسية', keep ? semH.value : '');
                    weekSel.innerHTML = '<option value="">كل الأسابيع</option>';
                }).catch(() => {});
        }

        gradeSel && gradeSel.addEventListener('change', function () {
            gradeH.value = this.value;
            const schoolId = schoolHidden.value;
            if (!schoolId) return;
            const url = "{{ url('admin/qb/scope/school') }}/" + schoolId + '/classes' + (this.value ? ('?grade=' + this.value) : '');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json()).then(d => opt(classSel, d.classes, 'كل الفصول', '')).catch(() => {});
        });
        classSel && classSel.addEventListener('change', function () { classH.value = this.value; });
        semSel && semSel.addEventListener('change', function () {
            semH.value = this.value;
            if (!this.value) { weekSel.innerHTML = '<option value="">كل الأسابيع</option>'; weekH.value=''; return; }
            fetch("{{ url('admin/qb/scope/semester') }}/" + this.value + '/weeks', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json()).then(d => opt(weekSel, d.weeks, 'كل الأسابيع', '')).catch(() => {});
        });
        weekSel && weekSel.addEventListener('change', function () { weekH.value = this.value; });

        // search + gender filter
        const search = root.querySelector('[data-scope-search]');
        const gender = root.querySelector('[data-scope-gender]');
        function filterSchools() {
            const term = (search.value || '').trim();
            const g = gender.value;
            root.querySelectorAll('[data-scope-school-card]').forEach(card => {
                const name = card.dataset.name || '';
                const sg = card.dataset.gender || '';
                const ok = (!term || name.includes(term)) && (!g || sg === g);
                card.style.display = ok ? '' : 'none';
            });
        }
        search && search.addEventListener('input', filterSchools);
        gender && gender.addEventListener('change', filterSchools);

        root.querySelector('[data-scope-clear]').addEventListener('click', function () {
            root.querySelectorAll('[data-scope-school-card]').forEach(c => c.classList.remove('selected'));
            [schoolHidden, compoundHidden, typeHidden, gradeH, classH, semH, weekH].forEach(h => h.value = '');
            cascade.style.display = 'none';
            search.value = ''; gender.value = ''; filterSchools();
        });

        // pre-load cascade on edit (a school is already selected)
        if (schoolHidden.value) loadSchool(schoolHidden.value, true);
    }
})();
</script>
@endpush
@endonce
