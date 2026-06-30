@extends('layouts.app')

@section('title', __('mailbox.compose'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';

    $isEdit = isset($draft) && $draft;
    $prefill = $prefill ?? [];
    $selectedRecipients = $selectedRecipients ?? [];

    // Resolve old() → prefill → draft for each field, in that precedence.
    $valSubject    = old('subject', $prefill['subject'] ?? ($isEdit ? $draft->subject : ''));
    $valImportance = old('importance', $prefill['importance'] ?? ($isEdit ? $draft->importance : 'normal'));
    $valBody       = old('body', $prefill['body'] ?? ($isEdit ? $draft->body : ''));

    $valTo = old('to', ! empty($selectedRecipients) ? $selectedRecipients : ($prefill['to'] ?? []));
    $valTo = array_map('intval', (array) $valTo);

    $formAction = $isEdit ? route('my.mailbox.update', $draft->id) : route('my.mailbox.store');
@endphp

@section('content')

{{-- Page header + breadcrumb --}}
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $isEdit ? __('mailbox.edit_draft') : __('mailbox.compose') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('mailbox.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('my.mailbox.index') }}">@lang('mailbox.breadcrumb_mailbox')</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? __('mailbox.edit_draft') : __('mailbox.compose') }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        <a href="{{ route('my.mailbox.index') }}" class="btn btn-outline-secondary">
            <x-svg-icon name="arrow-right" :size="15" /> @lang('mailbox.back')
        </a>
    </div>
</div>

<div class="ds-card card">
    <div class="ds-card-header card-header" style="display:flex;align-items:center;gap:.4rem">
        <x-svg-icon name="{{ $isEdit ? 'pencil-square' : 'envelope' }}" :size="16" />
        <h5 class="ds-card-title" style="margin:0">{{ $isEdit ? __('mailbox.edit_draft') : __('mailbox.compose') }}</h5>
    </div>
    <div class="card-body">
            <form action="{{ $formAction }}" method="POST"
                  enctype="multipart/form-data" id="composeForm">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="row">
                    {{-- Subject --}}
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="subject">@lang('mailbox.subject') <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="subject" maxlength="255"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ $valSubject }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Importance --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="importance">@lang('mailbox.importance') <span class="text-danger">*</span></label>
                            <select name="importance" id="importance"
                                    class="form-control @error('importance') is-invalid @enderror" required>
                                <option value="normal"    @selected($valImportance === 'normal')>@lang('mailbox.normal')</option>
                                <option value="important" @selected($valImportance === 'important')>@lang('mailbox.important_label')</option>
                                <option value="urgent"    @selected($valImportance === 'urgent')>@lang('mailbox.urgent')</option>
                            </select>
                            @error('importance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Recipients (#236): group + grade/class/job-title filters → searchable table --}}
                @php
                    // Seed the selection set from old()/draft/reply values, resolving
                    // display names from the school-scoped candidate pool.
                    $recipientNames = $recipients->pluck('name', 'id');
                    $initialSelected = collect($valTo)
                        ->map(fn ($id) => ['id' => (int) $id, 'name' => $recipientNames[$id] ?? ('#' . $id)])
                        ->values();
                @endphp

                <div class="form-group">
                    <label class="form-label" for="recipientGroup">@lang('mailbox.send_to_group') <span class="text-danger">*</span></label>
                    <select id="recipientGroup" class="form-control">
                        <option value="all">@lang('mailbox.group_all')</option>
                        <option value="students">@lang('mailbox.group_students')</option>
                        <option value="teachers">@lang('mailbox.group_teachers')</option>
                        <option value="parents">@lang('mailbox.group_parents')</option>
                        <option value="admins">@lang('mailbox.group_admins')</option>
                        <option value="job_titles">@lang('mailbox.group_job_titles')</option>
                    </select>
                    <small class="text-muted">@lang('mailbox.recipient_picker_hint')</small>
                </div>

                {{-- Grade/class grids (students & parents) + job-title grid (job_titles).
                     Rendered always; JS shows the grids relevant to the chosen group. --}}
                <x-audience-selector
                    :grids="['job_titles', 'grades', 'classes']"
                    :conditional="false"
                    :job-titles="$jobTitles"
                    :grade-levels="$gradeLevels"
                    :classes="$classes"
                />

                {{-- Search + select-all --}}
                <div class="form-group" style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end">
                    <div style="flex:1;min-width:220px">
                        <label class="form-label" for="recipientSearch">@lang('mailbox.search_recipients')</label>
                        <input type="search" id="recipientSearch" class="form-control"
                               placeholder="@lang('mailbox.search_recipients_placeholder')" autocomplete="off">
                    </div>
                    <button type="button" id="recipientSelectAll" class="btn btn-outline-primary">
                        <x-svg-icon name="check2-all" :size="15" /> @lang('mailbox.select_all_results')
                    </button>
                </div>

                {{-- Results table (AJAX) --}}
                <div class="form-group">
                    <div id="recipientsResults" class="ds-card card" style="padding:.4rem .6rem">
                        <p class="text-muted mb-0">@lang('mailbox.loading')</p>
                    </div>
                </div>

                {{-- Selected recipients (chips + the hidden to[] inputs that actually submit) --}}
                <div class="form-group">
                    <label class="form-label">
                        @lang('mailbox.selected_recipients')
                        <span id="selectedCount" class="badge ds-badge-navy">0</span>
                        <span class="text-danger">*</span>
                    </label>
                    <div id="selectedChips" class="d-flex flex-wrap"
                         style="gap:.4rem;min-height:34px;padding:.3rem;border:1px dashed var(--gray-200,#e5e7eb);border-radius:.5rem"></div>
                    <div id="selectedInputs"></div>
                    @error('to')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('to.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Related Student (parents only) --}}
                @if($children->isNotEmpty())
                    <div class="form-group">
                        <label for="related_student_id">@lang('mailbox.related_student')</label>
                        <select name="related_student_id" id="related_student_id"
                                class="form-control @error('related_student_id') is-invalid @enderror">
                            <option value="">— @lang('mailbox.select_student') —</option>
                            @foreach($children as $child)
                                <option value="{{ $child->id }}"
                                    @selected(old('related_student_id', $isEdit ? $draft->related_student_id : null) == $child->id)>
                                    {{ $child->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('related_student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                {{-- Body --}}
                <div class="form-group">
                    <label for="body">@lang('mailbox.body') <span class="text-danger">*</span></label>
                    <textarea name="body" id="body" rows="8"
                              class="form-control @error('body') is-invalid @enderror"
                              required>{{ $valBody }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Attachment --}}
                <div class="form-group">
                    <label for="attachment">@lang('mailbox.attachment')</label>
                    @if($isEdit && $draft->attachment_path)
                        <p class="mb-1">
                            <x-svg-icon name="paperclip" :size="14" class="ic-muted" />
                            <span class="text-muted small">{{ basename($draft->attachment_path) }}</span>
                            <span class="text-muted small">— @lang('mailbox.replace_attachment_hint')</span>
                        </p>
                    @endif
                    <input type="file" name="attachment" id="attachment"
                           class="form-control-file @error('attachment') is-invalid @enderror">
                    @error('attachment')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">@lang('mailbox.attachment_hint')</small>
                </div>

                <div class="form-actions">
                    <button type="submit" name="action" value="send" class="btn btn-primary">
                        <x-svg-icon name="send-fill" :size="15" /> @lang('mailbox.send')
                    </button>
                    <button type="submit" name="action" value="draft" class="btn btn-secondary mx-1">
                        <x-svg-icon name="save" :size="15" /> @lang('mailbox.save_draft')
                    </button>
                    <a href="{{ route('my.mailbox.index') }}" class="btn btn-light mx-1">
                        <x-svg-icon name="arrow-right" :size="15" /> @lang('mailbox.back')
                    </a>
                </div>
            </form>
        </div>
</div>
@endsection

@push('scripts')
<script>
window.__mailboxInitial = @json($initialSelected);
window.__recipientsSearchUrl = "{{ route('my.mailbox.recipients.search') }}";
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var url         = window.__recipientsSearchUrl;
    var groupSel    = document.getElementById('recipientGroup');
    var searchInp   = document.getElementById('recipientSearch');
    var resultsBox  = document.getElementById('recipientsResults');
    var chipsBox    = document.getElementById('selectedChips');
    var inputsBox   = document.getElementById('selectedInputs');
    var countBadge  = document.getElementById('selectedCount');
    var selectAllBtn = document.getElementById('recipientSelectAll');
    if (!groupSel || !resultsBox) { return; }

    // selected id(string) -> name. Survives pagination + search + group change.
    var selected = new Map();
    (window.__mailboxInitial || []).forEach(function (r) { selected.set(String(r.id), r.name); });

    // Last "select all results" payload (id+name) for the current filter set.
    var lastAll = [];

    function gridWrap(field) {
        var el = document.querySelector('input[name="' + field + '[]"]');
        return el ? el.closest('.form-group') : null;
    }
    var gradesWrap  = gridWrap('grade_levels');
    var classesWrap = gridWrap('class_ids');
    var jobsWrap    = gridWrap('job_title_ids');

    function checkedVals(field) {
        return Array.prototype.slice
            .call(document.querySelectorAll('input[name="' + field + '[]"]:checked'))
            .map(function (c) { return c.value; });
    }

    function syncGridVisibility() {
        var g = groupSel.value;
        var showGradeClass = (g === 'students' || g === 'parents');
        if (gradesWrap)  { gradesWrap.style.display  = showGradeClass ? '' : 'none'; }
        if (classesWrap) { classesWrap.style.display = showGradeClass ? '' : 'none'; }
        if (jobsWrap)    { jobsWrap.style.display    = (g === 'job_titles') ? '' : 'none'; }
    }

    function buildQuery(page) {
        var p = new URLSearchParams();
        p.set('group', groupSel.value);
        var s = searchInp.value.trim();
        if (s) { p.set('search', s); }
        checkedVals('grade_levels').forEach(function (v) { p.append('grade_levels[]', v); });
        checkedVals('class_ids').forEach(function (v) { p.append('class_ids[]', v); });
        checkedVals('job_title_ids').forEach(function (v) { p.append('job_title_ids[]', v); });
        if (page && page > 1) { p.set('page', page); }
        return p.toString();
    }

    function renderSelected() {
        countBadge.textContent = selected.size;
        chipsBox.innerHTML = '';
        inputsBox.innerHTML = '';
        selected.forEach(function (name, id) {
            var chip = document.createElement('span');
            chip.className = 'badge badge-light';
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .55rem;font-size:.8rem';
            chip.appendChild(document.createTextNode(name));

            var x = document.createElement('button');
            x.type = 'button';
            x.setAttribute('aria-label', 'remove');
            x.textContent = '×';
            x.style.cssText = 'border:0;background:transparent;cursor:pointer;font-size:1rem;line-height:1;color:var(--status-danger,#dc3545)';
            x.addEventListener('click', function () {
                selected.delete(id);
                renderSelected();
                syncRowChecks();
            });
            chip.appendChild(x);
            chipsBox.appendChild(chip);

            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'to[]';
            inp.value = id;
            inputsBox.appendChild(inp);
        });
    }

    function syncRowChecks() {
        var boxes = resultsBox.querySelectorAll('.recipient-check');
        boxes.forEach(function (cb) { cb.checked = selected.has(cb.value); });
        var pageAll = resultsBox.querySelector('#recipientPageAll');
        if (pageAll) {
            var on = resultsBox.querySelectorAll('.recipient-check:checked');
            pageAll.checked = boxes.length > 0 && on.length === boxes.length;
        }
    }

    function bindRows() {
        resultsBox.querySelectorAll('.recipient-check').forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (cb.checked) { selected.set(cb.value, cb.getAttribute('data-name')); }
                else { selected.delete(cb.value); }
                renderSelected();
                syncRowChecks();
            });
        });

        var pageAll = resultsBox.querySelector('#recipientPageAll');
        if (pageAll) {
            pageAll.addEventListener('change', function () {
                resultsBox.querySelectorAll('.recipient-check').forEach(function (cb) {
                    cb.checked = pageAll.checked;
                    if (pageAll.checked) { selected.set(cb.value, cb.getAttribute('data-name')); }
                    else { selected.delete(cb.value); }
                });
                renderSelected();
            });
        }

        // Intercept pagination links (works for Bootstrap or Tailwind markup).
        var pager = resultsBox.querySelector('#recipientPagination');
        if (pager) {
            pager.querySelectorAll('a[href]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    var page = 1;
                    try { page = new URL(a.href).searchParams.get('page') || 1; } catch (err) {}
                    load(page);
                });
            });
        }
    }

    function load(page) {
        resultsBox.innerHTML = '<p class="text-muted mb-0">@lang('mailbox.loading')</p>';
        fetch(url + '?' + buildQuery(page), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                resultsBox.innerHTML = d.html;
                lastAll = d.all || [];
                bindRows();
                syncRowChecks();
            })
            .catch(function () {
                resultsBox.innerHTML = '<p class="text-danger mb-0">@lang('mailbox.load_error')</p>';
            });
    }

    var debounce;
    function debouncedLoad() {
        clearTimeout(debounce);
        debounce = setTimeout(function () { load(1); }, 300);
    }

    groupSel.addEventListener('change', function () { syncGridVisibility(); load(1); });
    searchInp.addEventListener('input', debouncedLoad);

    // Any grade/class/job-title checkbox change re-runs the search.
    document.querySelectorAll(
        'input[name="grade_levels[]"], input[name="class_ids[]"], input[name="job_title_ids[]"]'
    ).forEach(function (cb) {
        cb.addEventListener('change', function () { load(1); });
    });

    selectAllBtn.addEventListener('click', function () {
        lastAll.forEach(function (r) { selected.set(String(r.id), r.name); });
        renderSelected();
        syncRowChecks();
    });

    // Block submit with no recipients (mirror server-side `to` required rule).
    var form = document.getElementById('composeForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            var sending = e.submitter && e.submitter.value === 'send';
            if (sending && selected.size === 0) {
                e.preventDefault();
                window.alert('@lang('mailbox.to_required_js')');
            }
        });
    }

    syncGridVisibility();
    renderSelected();
    load(1);
});
</script>
@endpush
