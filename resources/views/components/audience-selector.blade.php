{{--
    Reusable audience selector — checkbox grids (NOT Ctrl-multiselect) with a
    «تحديد الكل» toggle per grid and optional school-based filtering.

    Shared across Announcements (#232), School Calendar (#233), Mailbox (#236).
    Field names are kept identical to the legacy selects so existing
    Create/Update actions and DTOs keep working unchanged:
      job_title_ids[] · user_target_ids[] · role_target_ids[]
      grade_levels[]  · class_ids[]       · subject_ids[]

    Props
      grids         which sub-grids to render, in order
      conditional   wrap each grid in .ann-cond[data-show] (target_type toggling)
      schoolSelect  id of a <select> that filters grids by data-school (or null)
--}}
@props([
    'grids' => ['job_titles', 'users', 'roles', 'grades', 'classes', 'subjects'],
    'conditional' => true,
    'schoolSelect' => null,

    'jobTitles' => [],
    'users' => [],
    'roles' => [],
    'gradeLevels' => [],
    'classes' => [],
    'subjects' => [],

    'selectedJobTitles' => [],
    'selectedUsers' => [],
    'selectedRoles' => [],
    'selectedGrades' => [],
    'selectedClasses' => [],
    'selectedSubjects' => [],
])

@php
    $norm = fn ($c) => $c instanceof \Illuminate\Support\Collection ? $c : collect($c);

    // grid key => [label, target_type to match (null = always shown), field, empty, items]
    // items: each [value, text, checked, school(null=global)]
    $defs = [
        'job_titles' => [
            'label' => 'اختر المسميات الوظيفية',
            'show'  => 'job_titles',
            'field' => 'job_title_ids',
            'empty' => 'لا توجد مسميات وظيفية متاحة.',
            'items' => $norm($jobTitles)->map(fn ($jt) => [
                'value'   => $jt->id,
                'text'    => $jt->name_ar,
                'checked' => in_array($jt->id, $selectedJobTitles),
                'school'  => $jt->school_id ?? null,
            ]),
        ],
        'users' => [
            'label' => 'اختر المستخدمين',
            'show'  => 'specific_users',
            'field' => 'user_target_ids',
            'empty' => 'لا يوجد مستخدمون متاحون.',
            'items' => $norm($users)->map(fn ($u) => [
                'value'   => $u->id,
                'text'    => $u->name,
                'checked' => in_array($u->id, $selectedUsers),
                'school'  => $u->school_id ?? null,
            ]),
        ],
        'roles' => [
            'label' => 'اختر الأدوار',
            'show'  => 'specific_roles',
            'field' => 'role_target_ids',
            'empty' => 'لا توجد أدوار متاحة.',
            'items' => $norm($roles)->map(fn ($r) => [
                'value'   => $r->id,
                'text'    => $r->name,
                'checked' => in_array($r->id, $selectedRoles),
                'school'  => null,
            ]),
        ],
        'grades' => [
            'label' => 'الصفوف',
            'show'  => 'students',
            'field' => 'grade_levels',
            'empty' => 'لا توجد صفوف متاحة.',
            'items' => $norm($gradeLevels)->map(fn ($g) => [
                'value'   => $g,
                'text'    => 'الصف ' . $g,
                'checked' => in_array($g, $selectedGrades),
                'school'  => null,
            ]),
        ],
        'classes' => [
            'label' => 'الفصول',
            'show'  => 'students',
            'field' => 'class_ids',
            'empty' => 'لا توجد فصول متاحة.',
            'items' => $norm($classes)->map(fn ($c) => [
                'value'   => $c->id,
                'text'    => $c->name . ' (صف ' . $c->grade_level . ')',
                'checked' => in_array($c->id, $selectedClasses),
                'school'  => $c->school_id ?? null,
            ]),
        ],
        'subjects' => [
            'label' => 'المواد (اختياري)',
            'show'  => null, // always visible
            'field' => 'subject_ids',
            'empty' => 'لا توجد مواد متاحة.',
            'items' => $norm($subjects)->map(fn ($s) => [
                'value'   => $s->id,
                'text'    => $s->name,
                'checked' => in_array($s->id, $selectedSubjects),
                'school'  => $s->school_id ?? null,
            ]),
        ],
    ];
@endphp

<div class="aud-selector" @if($schoolSelect) data-school-select="{{ $schoolSelect }}" @endif>
    @foreach($grids as $key)
        @continue(!isset($defs[$key]))
        @php $g = $defs[$key]; @endphp

        <div class="form-group {{ $conditional && $g['show'] ? 'ann-cond' : '' }}"
             @if($conditional && $g['show']) data-show="{{ $g['show'] }}" @endif>
            <label class="form-label">{{ $g['label'] }}</label>

            @if($g['items']->isEmpty())
                <p class="text-muted" style="margin:.2rem 0 0">{{ $g['empty'] }}</p>
            @else
                <div class="aud-grid">
                    <label class="aud-selectall-row">
                        <input type="checkbox" class="aud-selectall">
                        <span>تحديد الكل</span>
                    </label>
                    <div class="aud-grid-items">
                        @foreach($g['items'] as $item)
                            <label class="aud-item">
                                <input type="checkbox" name="{{ $g['field'] }}[]"
                                       value="{{ $item['value'] }}"
                                       @if(!is_null($item['school'])) data-school="{{ $item['school'] }}" @endif
                                       @checked($item['checked'])>
                                <span>{{ $item['text'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>

@once
@push('scripts')
<script>
(function () {
    function items(grid) {
        return Array.prototype.slice.call(grid.querySelectorAll('.aud-item input[type=checkbox]'));
    }
    function visible(box) {
        var item = box.closest('.aud-item');
        return item && !item.hidden;
    }
    function initGrid(grid) {
        var all = grid.querySelector('.aud-selectall');
        function refresh() {
            if (!all) { return; }
            var vis = items(grid).filter(visible);
            var on  = vis.filter(function (b) { return b.checked; });
            all.checked = vis.length > 0 && on.length === vis.length;
            all.indeterminate = on.length > 0 && on.length < vis.length;
        }
        if (all) {
            all.addEventListener('change', function () {
                items(grid).filter(visible).forEach(function (b) { b.checked = all.checked; });
                refresh();
            });
        }
        grid.addEventListener('change', function (e) {
            if (e.target.matches('.aud-item input[type=checkbox]')) { refresh(); }
        });
        grid.__audRefresh = refresh;
        refresh();
    }
    document.querySelectorAll('.aud-grid').forEach(initGrid);

    // School-scoped filtering: hide+uncheck items whose data-school != chosen school.
    document.querySelectorAll('.aud-selector[data-school-select]').forEach(function (root) {
        var sel = document.getElementById(root.dataset.schoolSelect);
        if (!sel) { return; }
        function apply() {
            var sid = sel.value;
            root.querySelectorAll('.aud-item').forEach(function (item) {
                var inp = item.querySelector('input');
                var os = inp.getAttribute('data-school');
                var match = !os || os === sid;
                item.hidden = !match;
                if (!match) { inp.checked = false; }
            });
            root.querySelectorAll('.aud-grid').forEach(function (gr) {
                if (gr.__audRefresh) { gr.__audRefresh(); }
            });
        }
        sel.addEventListener('change', apply);
        apply();
    });
})();
</script>
<style>
.aud-grid {
    border: 1px solid var(--gray-200, #e5e7eb);
    border-radius: .5rem;
    padding: .6rem .75rem;
    max-height: 320px;
    overflow-y: auto;
}
.aud-selectall-row {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-weight: 700;
    cursor: pointer;
    margin: 0 0 .5rem;
    padding-bottom: .45rem;
    border-bottom: 1px dashed var(--gray-200, #e5e7eb);
}
.aud-grid-items {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: .35rem .9rem;
}
.aud-item {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-weight: 500;
    cursor: pointer;
    margin: 0;
}
.aud-item input,
.aud-selectall-row input { flex: 0 0 auto; }
</style>
@endpush
@endonce
