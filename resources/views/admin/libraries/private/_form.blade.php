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
        <select name="audiences[class][ids][]" id="lib-classes" class="form-control select2" multiple data-placeholder="@lang('libraries.private.fields.classes')">
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
        <select name="audiences[user][ids][]" id="lib-students" class="form-control select2" multiple disabled
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
        <select name="audiences[teacher][ids][]" id="lib-teachers" class="form-control select2" multiple disabled
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
// Select2-aware cascade (cards #119 + #124): the audience selects are Select2 dropdowns,
// so we bind via jQuery and refresh Select2 after rebuilding options.
jQuery(function ($) {
    var root = document.getElementById('lib-audiences');
    if (!root) return;
    var url = root.dataset.membersUrl;
    var $classSel = $('#lib-classes');
    var $studentSel = $('#lib-students');
    var $teacherSel = $('#lib-teachers');

    // Re-init these selects with the search box always visible (card #128); the global
    // init hides search for short lists (minimumResultsForSearch: 6).
    if ($.fn.select2) {
        $classSel.add($studentSel).add($teacherSel).each(function () {
            var $s = $(this);
            if ($s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
            $s.select2({
                theme: 'bootstrap4',
                width: '100%',
                dir: document.documentElement.getAttribute('dir') || 'rtl',
                minimumResultsForSearch: 0,
                placeholder: $s.data('placeholder') || '',
            });
        });
    }

    var T = {
        loading: @json(__('libraries.private.fields.loading')),
        noStudents: @json(__('libraries.private.fields.no_students')),
        noTeachers: @json(__('libraries.private.fields.no_teachers')),
        chooseFirst: @json(__('libraries.private.fields.choose_class_first')),
    };

    function refresh($sel) { if ($sel.hasClass('select2-hidden-accessible')) $sel.trigger('change.select2'); }
    function hint($sel, text) {
        var h = root.querySelector('.lib-hint[data-for="' + $sel.attr('id') + '"]');
        if (h) h.textContent = text || '';
    }
    function currentSelection($sel) { return ($sel.val() || []).map(String); }

    function rebuild($sel, items, keepIds, emptyText) {
        var keep = new Set((keepIds || []).map(String));
        $sel.empty();
        items.forEach(function (it) {
            var o = new Option(it.name, it.id, false, keep.has(String(it.id)));
            $sel.append(o);
        });
        $sel.prop('disabled', items.length === 0);
        hint($sel, items.length === 0 ? emptyText : '');
        refresh($sel);
    }

    function load() {
        var ids = ($classSel.val() || []);
        if (ids.length === 0) {
            $studentSel.empty().prop('disabled', true); hint($studentSel, T.chooseFirst); refresh($studentSel);
            $teacherSel.empty().prop('disabled', true); hint($teacherSel, T.chooseFirst); refresh($teacherSel);
            return;
        }
        var keepStudents = currentSelection($studentSel);
        var keepTeachers = currentSelection($teacherSel);
        hint($studentSel, T.loading); hint($teacherSel, T.loading);

        var qs = ids.map(function (id) { return 'class_ids[]=' + encodeURIComponent(id); }).join('&');
        fetch(url + '?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                rebuild($studentSel, data.students || [], keepStudents, T.noStudents);
                rebuild($teacherSel, data.teachers || [], keepTeachers, T.noTeachers);
            })
            .catch(function () { hint($studentSel, ''); hint($teacherSel, ''); });
    }

    $classSel.on('change', load);

    $('.lib-select-all').on('click', function () {
        var $sel = $('#' + $(this).data('target'));
        if (!$sel.length || $sel.prop('disabled')) return;
        $sel.find('option').prop('selected', true);
        refresh($sel);
    });

    // On edit: if classes are already selected, load their members and keep existing picks.
    if (($classSel.val() || []).length > 0) load();
});
</script>
@endpush
