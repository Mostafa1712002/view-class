@csrf
<div class="row">
    <div class="col-md-8 col-12 lib-field">
        <label class="form-label">@lang('libraries.private.fields.title') <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $library->title) }}" class="form-control" required maxlength="255" />
    </div>
    <div class="col-md-4 col-12 lib-field d-flex align-items-end">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0" />
            <input type="checkbox" name="is_active" value="1" id="lib-is-active" class="form-check-input" @checked(old('is_active', $library->is_active ?? true)) />
            <label class="form-check-label" for="lib-is-active">@lang('libraries.private.fields.is_active')</label>
        </div>
    </div>
    <div class="col-12 lib-field">
        <label class="form-label">@lang('libraries.private.fields.description')</label>
        <textarea name="description" rows="3" class="form-control">{{ old('description', $library->description) }}</textarea>
    </div>
</div>

<div class="lib-divider"></div>

<h6 class="lib-section-title"><i class="la la-user-shield"></i> @lang('libraries.private.fields.audiences')</h6>
<p class="text-muted small mb-3">@lang('libraries.private.fields.audiences_help')</p>

@php
    $existingClasses = collect($currentAudiences['class'] ?? [])->pluck('audience_id')->all();
    $existingStudents = collect($currentAudiences['user'] ?? [])->pluck('audience_id')->all();
    $existingTeachers = collect($currentAudiences['teacher'] ?? [])->pluck('audience_id')->all();
@endphp

<div class="row" id="lib-audiences"
     data-members-url="{{ route('admin.libraries.private.class-members') }}"
     data-selected-students="{{ json_encode(array_values($existingStudents)) }}"
     data-selected-teachers="{{ json_encode(array_values($existingTeachers)) }}">
    <div class="col-md-4 col-12 lib-field">
        <label class="form-label">@lang('libraries.private.fields.classes')</label>
        <select name="audiences[class][ids][]" id="lib-classes" class="form-select" multiple size="6">
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(in_array($c->id, $existingClasses))>{{ $c->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="audiences[class][type]" value="class" />
    </div>
    <div class="col-md-4 col-12 lib-field">
        <label class="form-label d-flex justify-content-between align-items-center">
            <span>@lang('libraries.private.fields.students')</span>
            <button type="button" class="btn btn-link btn-sm p-0 lib-select-all" data-target="lib-students">@lang('libraries.private.fields.select_all')</button>
        </label>
        <select name="audiences[user][ids][]" id="lib-students" class="form-select" multiple size="6" disabled
                data-placeholder="@lang('libraries.private.fields.choose_class_first')">
            @foreach($selectedStudents as $s)
                <option value="{{ $s->id }}" selected>{{ $s->name }}</option>
            @endforeach
        </select>
        <small class="text-muted lib-hint" data-for="lib-students">@lang('libraries.private.fields.choose_class_first')</small>
        <input type="hidden" name="audiences[user][type]" value="user" />
    </div>
    <div class="col-md-4 col-12 lib-field">
        <label class="form-label d-flex justify-content-between align-items-center">
            <span>@lang('libraries.private.fields.teachers')</span>
            <button type="button" class="btn btn-link btn-sm p-0 lib-select-all" data-target="lib-teachers">@lang('libraries.private.fields.select_all')</button>
        </label>
        <select name="audiences[teacher][ids][]" id="lib-teachers" class="form-select" multiple size="6" disabled
                data-placeholder="@lang('libraries.private.fields.choose_class_first')">
            @foreach($selectedTeachers as $t)
                <option value="{{ $t->id }}" selected>{{ $t->name }}</option>
            @endforeach
        </select>
        <small class="text-muted lib-hint" data-for="lib-teachers">@lang('libraries.private.fields.choose_class_first')</small>
        <input type="hidden" name="audiences[teacher][type]" value="teacher" />
    </div>
</div>

<div class="mt-3 d-flex gap-2 flex-wrap">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('libraries.actions.save')</button>
    <a href="{{ route('admin.libraries.private.index') }}" class="btn btn-outline-secondary">@lang('libraries.actions.cancel')</a>
</div>

@push('scripts')
<script>
(function () {
    var root = document.getElementById('lib-audiences');
    if (!root) return;
    var url = root.dataset.membersUrl;
    var classSel = document.getElementById('lib-classes');
    var studentSel = document.getElementById('lib-students');
    var teacherSel = document.getElementById('lib-teachers');
    var T = {
        loading: @json(__('libraries.private.fields.loading')),
        noStudents: @json(__('libraries.private.fields.no_students')),
        noTeachers: @json(__('libraries.private.fields.no_teachers')),
        chooseFirst: @json(__('libraries.private.fields.choose_class_first')),
    };

    function hint(sel, text) {
        var h = root.querySelector('.lib-hint[data-for="' + sel.id + '"]');
        if (h) h.textContent = text || '';
    }

    // Preserve the user's current selection + the server-rendered (already attached) ids.
    function currentSelection(sel) {
        return Array.from(sel.options).filter(function (o) { return o.selected; }).map(function (o) { return o.value; });
    }

    function rebuild(sel, items, keepIds, emptyText) {
        var keep = new Set((keepIds || []).map(String));
        sel.innerHTML = '';
        items.forEach(function (it) {
            var o = document.createElement('option');
            o.value = it.id;
            o.textContent = it.name;
            if (keep.has(String(it.id))) o.selected = true;
            sel.appendChild(o);
        });
        sel.disabled = items.length === 0;
        hint(sel, items.length === 0 ? emptyText : '');
    }

    function selectedClassIds() {
        return Array.from(classSel.selectedOptions).map(function (o) { return o.value; });
    }

    function load() {
        var ids = selectedClassIds();
        if (ids.length === 0) {
            studentSel.innerHTML = ''; studentSel.disabled = true; hint(studentSel, T.chooseFirst);
            teacherSel.innerHTML = ''; teacherSel.disabled = true; hint(teacherSel, T.chooseFirst);
            return;
        }
        var keepStudents = currentSelection(studentSel);
        var keepTeachers = currentSelection(teacherSel);
        hint(studentSel, T.loading); hint(teacherSel, T.loading);

        var qs = ids.map(function (id) { return 'class_ids[]=' + encodeURIComponent(id); }).join('&');
        fetch(url + '?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                rebuild(studentSel, data.students || [], keepStudents, T.noStudents);
                rebuild(teacherSel, data.teachers || [], keepTeachers, T.noTeachers);
            })
            .catch(function () { hint(studentSel, ''); hint(teacherSel, ''); });
    }

    classSel.addEventListener('change', load);

    root.querySelectorAll('.lib-select-all').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var sel = document.getElementById(btn.dataset.target);
            if (!sel || sel.disabled) return;
            Array.from(sel.options).forEach(function (o) { o.selected = true; });
        });
    });

    // On edit: if classes are already selected, load their members and keep existing picks.
    if (selectedClassIds().length > 0) load();
})();
</script>
@endpush
