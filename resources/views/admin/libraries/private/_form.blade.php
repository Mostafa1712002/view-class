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

<div class="row lib-audiences-row" id="lib-audiences"
     data-members-url="{{ route('admin.libraries.private.class-members') }}"
     data-selected-students="{{ json_encode(array_values($existingStudents)) }}"
     data-selected-teachers="{{ json_encode(array_values($existingTeachers)) }}">

    {{-- Classes --}}
    <div class="col-lg-4 col-md-6 col-12 lib-field">
        <div class="lib-aud-col">
            <div class="lib-aud-head">
                <span class="lib-aud-title"><i class="la la-layer-group"></i> @lang('libraries.private.fields.classes')</span>
                <span class="lib-aud-badge" title="@lang('libraries.private.fields.selected_count')"><i class="la la-check-circle"></i> <span class="lib-aud-count" data-for="lib-classes">0</span></span>
            </div>
            <select name="audiences[class][ids][]" id="lib-classes" class="form-control select2" multiple data-placeholder="@lang('libraries.private.fields.pick_classes')">
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected(in_array($c->id, $existingClasses))>{{ $c->name }}</option>
                @endforeach
            </select>
            <div class="lib-aud-foot">
                <span class="lib-aud-tools"></span>
                <small class="text-muted lib-hint" data-for="lib-classes"></small>
            </div>
        </div>
        <input type="hidden" name="audiences[class][type]" value="class" />
    </div>

    {{-- Students --}}
    <div class="col-lg-4 col-md-6 col-12 lib-field">
        <div class="lib-aud-col">
            <div class="lib-aud-head">
                <span class="lib-aud-title"><i class="la la-user-graduate"></i> @lang('libraries.private.fields.students')</span>
                <span class="lib-aud-badge" title="@lang('libraries.private.fields.selected_count')"><i class="la la-check-circle"></i> <span class="lib-aud-count" data-for="lib-students">0</span></span>
            </div>
            <select name="audiences[user][ids][]" id="lib-students" class="form-control select2" multiple disabled
                    data-placeholder="@lang('libraries.private.fields.choose_class_first')"
                    data-placeholder-ready="@lang('libraries.private.fields.pick_students')">
                @foreach($selectedStudents as $s)
                    <option value="{{ $s->id }}" selected>{{ $s->name }}</option>
                @endforeach
            </select>
            <div class="lib-aud-foot">
                <span class="lib-aud-tools">
                    <button type="button" class="lib-aud-link lib-select-all" data-target="lib-students"><i class="la la-check-double"></i> @lang('libraries.private.fields.select_all')</button>
                    <button type="button" class="lib-aud-link lib-clear-all" data-target="lib-students"><i class="la la-eraser"></i> @lang('libraries.private.fields.clear_all')</button>
                </span>
                <small class="text-muted lib-hint" data-for="lib-students"></small>
            </div>
        </div>
        <input type="hidden" name="audiences[user][type]" value="user" />
    </div>

    {{-- Teachers --}}
    <div class="col-lg-4 col-md-6 col-12 lib-field">
        <div class="lib-aud-col">
            <div class="lib-aud-head">
                <span class="lib-aud-title"><i class="la la-chalkboard-teacher"></i> @lang('libraries.private.fields.teachers')</span>
                <span class="lib-aud-badge" title="@lang('libraries.private.fields.selected_count')"><i class="la la-check-circle"></i> <span class="lib-aud-count" data-for="lib-teachers">0</span></span>
            </div>
            <select name="audiences[teacher][ids][]" id="lib-teachers" class="form-control select2" multiple disabled
                    data-placeholder="@lang('libraries.private.fields.choose_class_first')"
                    data-placeholder-ready="@lang('libraries.private.fields.pick_teachers')">
                @foreach($selectedTeachers as $t)
                    <option value="{{ $t->id }}" selected>{{ $t->name }}</option>
                @endforeach
            </select>
            <div class="lib-aud-foot">
                <span class="lib-aud-tools">
                    <button type="button" class="lib-aud-link lib-select-all" data-target="lib-teachers"><i class="la la-check-double"></i> @lang('libraries.private.fields.select_all')</button>
                    <button type="button" class="lib-aud-link lib-clear-all" data-target="lib-teachers"><i class="la la-eraser"></i> @lang('libraries.private.fields.clear_all')</button>
                </span>
                <small class="text-muted lib-hint" data-for="lib-teachers"></small>
            </div>
        </div>
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
    // The hint inside the box (placeholder) and the hint line below must never duplicate (card #128):
    // disabled state -> placeholder shows "choose class first", the line stays empty;
    // enabled state  -> placeholder shows "search & select", the line is only for loading/empty results.
    function setPlaceholder($sel, text) {
        $sel.attr('data-placeholder', text);
        $sel.next('.select2-container').find('.select2-search__field').attr('placeholder', text);
    }
    function updateCount($sel) {
        var n = ($sel.val() || []).length;
        var el = root.querySelector('.lib-aud-count[data-for="' + $sel.attr('id') + '"]');
        if (el) el.textContent = n;
        var col = $sel.closest('.lib-aud-col');
        if (col.length) col.toggleClass('has-selection', n > 0);
    }
    function currentSelection($sel) { return ($sel.val() || []).map(String); }

    function disableEmpty($sel) {
        $sel.empty().prop('disabled', true);
        setPlaceholder($sel, T.chooseFirst);
        hint($sel, '');
        refresh($sel); updateCount($sel);
    }

    function rebuild($sel, items, keepIds, emptyText, readyText) {
        var keep = new Set((keepIds || []).map(String));
        $sel.empty();
        items.forEach(function (it) {
            var o = new Option(it.name, it.id, false, keep.has(String(it.id)));
            $sel.append(o);
        });
        var empty = items.length === 0;
        $sel.prop('disabled', empty);
        setPlaceholder($sel, empty ? T.chooseFirst : readyText);
        hint($sel, empty ? emptyText : '');
        refresh($sel); updateCount($sel);
    }

    function load() {
        var ids = ($classSel.val() || []);
        if (ids.length === 0) { disableEmpty($studentSel); disableEmpty($teacherSel); return; }
        var keepStudents = currentSelection($studentSel);
        var keepTeachers = currentSelection($teacherSel);
        hint($studentSel, T.loading); hint($teacherSel, T.loading);

        var qs = ids.map(function (id) { return 'class_ids[]=' + encodeURIComponent(id); }).join('&');
        fetch(url + '?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                rebuild($studentSel, data.students || [], keepStudents, T.noStudents, $studentSel.data('placeholder-ready'));
                rebuild($teacherSel, data.teachers || [], keepTeachers, T.noTeachers, $teacherSel.data('placeholder-ready'));
            })
            .catch(function () { hint($studentSel, ''); hint($teacherSel, ''); });
    }

    $classSel.on('change', function () { updateCount($classSel); load(); });
    $studentSel.on('change', function () { updateCount($studentSel); });
    $teacherSel.on('change', function () { updateCount($teacherSel); });

    $('.lib-select-all').on('click', function () {
        var $sel = $('#' + $(this).data('target'));
        if (!$sel.length || $sel.prop('disabled')) return;
        $sel.find('option').prop('selected', true);
        refresh($sel); updateCount($sel);
    });
    $('.lib-clear-all').on('click', function () {
        var $sel = $('#' + $(this).data('target'));
        if (!$sel.length || $sel.prop('disabled')) return;
        $sel.find('option').prop('selected', false);
        refresh($sel); updateCount($sel);
    });

    // Initial counts (covers pre-selected audiences on edit).
    updateCount($classSel); updateCount($studentSel); updateCount($teacherSel);

    // On edit: if classes are already selected, load their members and keep existing picks.
    if (($classSel.val() || []).length > 0) load();
});
</script>
@endpush
