@csrf

<div class="qb-form-section">
    <h3 class="qb-form-section__title">@lang('question_banks.form.section_basic')</h3>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">@lang('question_banks.subject_for_bank') <span class="text-danger">*</span></label>
            @php
                $selectedSubjectId = old('subject_id', $bank->exists ? optional($bank->subjects->first())->id : null);
            @endphp
            <select name="subject_id" id="qb-subject-select"
                    class="form-control @error('subject_id') is-invalid @enderror" required>
                <option value="">— @lang('question_banks.subject_for_bank') —</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}"
                            data-name="{{ $subject->name }}"
                            @selected($selectedSubjectId == $subject->id)>
                        {{ $subject->name }}@if($subject->name_en) ({{ $subject->name_en }})@endif
                    </option>
                @endforeach
            </select>
            @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">@lang('question_banks.name_auto_hint')</small>
        </div>
        {{-- Hidden fields: name_ar auto-populated by JS from subject selection --}}
        <input type="hidden" name="name_ar" id="qb-name-ar-hidden"
               value="{{ old('name_ar', $bank->name_ar) }}">
        <input type="hidden" name="name_en" id="qb-name-en-hidden"
               value="{{ old('name_en', $bank->name_en) }}">
        @php
            $canManageGeneral = auth()->user()?->isSuperAdmin() || auth()->user()?->isSchoolAdmin();
        @endphp
        <div class="col-md-4 mb-3">
            <label class="form-label">@lang('question_banks.form.visibility') <span class="text-danger">*</span></label>
            <select name="visibility" class="form-control @error('visibility') is-invalid @enderror" required>
                @foreach($visibilities as $k => $label)
                    @if($k === 'public' && ! $canManageGeneral)
                        {{-- Non-admin: show but disable public option --}}
                        <option value="{{ $k }}" disabled>{{ $label }}</option>
                    @else
                        <option value="{{ $k }}" @selected(old('visibility', $bank->visibility ?? 'private') === $k)>{{ $label }}</option>
                    @endif
                @endforeach
            </select>
            @error('visibility')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted d-block mt-1">
                <strong>@lang('question_banks.visibility_public'):</strong> @lang('question_banks.visibility_public_hint')<br>
                <strong>@lang('question_banks.visibility_private'):</strong> @lang('question_banks.visibility_private_hint')
                @if($canManageGeneral)
                    <br><em class="text-warning"><i class="la la-info-circle"></i> @lang('question_banks.notice_general_approved_only')</em>
                @endif
            </small>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">@lang('question_banks.form.status') <span class="text-danger">*</span></label>
            <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                @foreach($statuses as $k => $label)
                    <option value="{{ $k }}" @selected(old('status', $bank->status ?? 'active') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">@lang('question_banks.form.source') <span class="text-danger">*</span></label>
            <select name="source" class="form-control @error('source') is-invalid @enderror" required>
                @foreach($sources as $k => $label)
                    <option value="{{ $k }}" @selected(old('source', $bank->source ?? 'manual') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

@php
    $sharedSchoolIds = old('school_ids', $bank->exists ? ($bank->sharedSchools?->pluck('id')->all() ?? []) : []);
    $isPublic = old('visibility', $bank->visibility ?? 'private') === 'public';
@endphp
<div class="qb-form-section" id="qb-share-section" style="{{ $isPublic ? '' : 'display:none;' }}">
    <h3 class="qb-form-section__title">@lang('question_banks.form.section_sharing')</h3>
    <div class="row">
        <div class="col-12 mb-2">
            <small class="text-muted">@lang('question_banks.form.sharing_hint')</small>
        </div>
        @if($shareSchools->isEmpty())
            <div class="col-12"><p class="text-muted small mb-0">—</p></div>
        @else
            <div class="col-12">
                <div class="qb-subject-grid">
                    @foreach($shareSchools as $school)
                        <label class="qb-subject-chip">
                            <input type="checkbox" name="school_ids[]" value="{{ $school->id }}"
                                   {{ in_array($school->id, $sharedSchoolIds) ? 'checked' : '' }}>
                            <span>{{ $school->name_ar ?? $school->name }}</span>
                        </label>
                    @endforeach
                </div>
                <small class="text-muted d-block mt-2">@lang('question_banks.form.sharing_empty_means_all')</small>
            </div>
        @endif
    </div>
</div>

<div class="qb-form-section">
    <h3 class="qb-form-section__title">@lang('question_banks.form.section_education')</h3>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">@lang('question_banks.form.grade_level')</label>
            <select name="grade_level" class="form-control @error('grade_level') is-invalid @enderror">
                <option value="">@lang('question_banks.grade_any')</option>
                @foreach($grades as $g => $label)
                    <option value="{{ $g }}" @selected((string)old('grade_level', $bank->grade_level) === (string)$g)>{{ $label }}</option>
                @endforeach
            </select>
            @error('grade_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">@lang('question_banks.form.category_type')</label>
            <select name="category_type" class="form-control @error('category_type') is-invalid @enderror">
                <option value="">@lang('question_banks.category_none')</option>
                @foreach($categories as $k => $label)
                    <option value="{{ $k }}" @selected(old('category_type', $bank->category_type) === $k)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">@lang('question_banks.form.subjects')</label>
            @php $selectedSubjects = old('subject_ids', $bank->subjects?->pluck('id')->all() ?? []); @endphp
            @if($subjects->isEmpty())
                <p class="text-muted small mb-0">—</p>
            @else
                <div class="qb-subject-grid">
                    @foreach($subjects as $subject)
                        <label class="qb-subject-chip">
                            <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                                   {{ in_array($subject->id, $selectedSubjects) ? 'checked' : '' }}>
                            <span>{{ $subject->name }}@if($subject->name_en) <small class="text-muted">({{ $subject->name_en }})</small>@endif</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="qb-form-section">
    <h3 class="qb-form-section__title">@lang('question_banks.form.section_permissions')</h3>
    <div class="row">
        <div class="col-12 mb-2">
            <small class="text-muted">@lang('question_banks.form.editors') / @lang('question_banks.form.viewers')</small>
        </div>
        @php
            $memberRoles = old('member_roles', []);
            if (empty($memberRoles) && $bank->exists) {
                foreach ($bank->members as $m) { $memberRoles[$m->id] = $m->pivot->role; }
            }
        @endphp
        @if($teachers->isEmpty())
            <div class="col-12"><p class="text-muted small">—</p></div>
        @else
            @foreach($teachers as $teacher)
                <div class="col-md-4 mb-2">
                    <div class="qb-teacher-row">
                        <span class="qb-teacher-name">{{ $teacher->name ?? $teacher->username }}</span>
                        <select name="member_roles[{{ $teacher->id }}]" class="form-select form-select-sm">
                            <option value="" {{ ($memberRoles[$teacher->id] ?? '') === '' ? 'selected' : '' }}>
                                @lang('question_banks.form.role_none')
                            </option>
                            <option value="viewer" {{ ($memberRoles[$teacher->id] ?? '') === 'viewer' ? 'selected' : '' }}>
                                @lang('question_banks.form.role_viewer')
                            </option>
                            <option value="editor" {{ ($memberRoles[$teacher->id] ?? '') === 'editor' ? 'selected' : '' }}>
                                @lang('question_banks.form.role_editor')
                            </option>
                        </select>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

<div class="qb-form-section">
    <h3 class="qb-form-section__title">@lang('question_banks.form.section_future')</h3>
    <div class="row">
        <div class="col-12 mb-3">
            <label class="qb-toggle">
                <input type="hidden" name="is_ana_qudurat_linkable" value="0">
                <input type="checkbox" name="is_ana_qudurat_linkable" value="1"
                       {{ old('is_ana_qudurat_linkable', $bank->is_ana_qudurat_linkable) ? 'checked' : '' }}>
                <span>@lang('question_banks.form.is_al_awwal_linkable')</span>
            </label>
        </div>
        <div class="col-12 mb-3">
            <label class="qb-toggle">
                <input type="hidden" name="exportable" value="0">
                <input type="checkbox" name="exportable" value="1"
                       {{ old('exportable', $bank->exists ? $bank->exportable : true) ? 'checked' : '' }}>
                <span>@lang('question_banks.form.exportable')</span>
            </label>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">@lang('question_banks.form.external_platform')</label>
            <input type="text" name="external_platform" value="{{ old('external_platform', $bank->external_platform) }}"
                   class="form-control @error('external_platform') is-invalid @enderror"
                   placeholder="@lang('question_banks.form.external_platform_hint')">
            @error('external_platform')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<script>
(function () {
    // Auto-derive bank name from selected subject
    var subSel    = document.getElementById('qb-subject-select');
    var nameAr    = document.getElementById('qb-name-ar-hidden');
    var nameEn    = document.getElementById('qb-name-en-hidden');
    if (subSel && nameAr) {
        function syncName() {
            var opt = subSel.options[subSel.selectedIndex];
            if (opt && opt.value) {
                nameAr.value = 'بنك أسئلة ' + (opt.dataset.name || opt.text.split('(')[0].trim());
                if (nameEn && opt.dataset.nameEn) {
                    nameEn.value = 'Question Bank — ' + opt.dataset.nameEn;
                }
            }
        }
        subSel.addEventListener('change', syncName);
        // Sync on load only if no existing name_ar (new bank)
        if (!nameAr.value) { syncName(); }
    }
})();
(function () {
    // Show/hide sharing section based on visibility
    var visSel = document.querySelector('select[name="visibility"]');
    var shareSection = document.getElementById('qb-share-section');
    if (!visSel || !shareSection) return;
    function sync() {
        shareSection.style.display = visSel.value === 'public' ? '' : 'none';
    }
    visSel.addEventListener('change', sync);
    sync();
})();
</script>

<div class="qb-form-actions">
    <a href="{{ route('admin.question-banks.index') }}" class="btn-reset">
        @lang('question_banks.form.cancel')
    </a>
    <button type="submit" class="btn-gold">
        <i class="la la-save"></i> @lang('question_banks.form.save')
    </button>
</div>
