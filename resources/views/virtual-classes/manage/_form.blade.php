@php
    $isRtl        = app()->getLocale() === 'ar';
    $audienceOpts = [
        'all'      => __('virtual_classes.audience_all'),
        'students' => __('virtual_classes.audience_students'),
        'parents'  => __('virtual_classes.audience_parents'),
        'teachers' => __('virtual_classes.audience_teachers'),
        'staff'    => __('virtual_classes.audience_staff'),
    ];
    $selectedAudience = old('audience', $vc ? ($vc->audience ?? ['all']) : ['all']);

    // Teacher list = real teachers/school-admins of the ACTIVE school, matching the
    // store/update validation (which scopes teacher_id to the active school). Do NOT
    // include super-admins or default to the current user — a super-admin acting in a
    // school is not a bookable teacher and would fail the school-scoped validation.
    $authUser  = auth()->user();
    $schoolId  = session('scope.school_id') ?? $authUser->school_id;
    $teachers  = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('slug', ['teacher', 'school-admin']))
                    ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                    ->orderBy('name')
                    ->get(['id', 'name', 'name_ar']);
    $defaultTeacher = old('teacher_id', $vc?->teacher_id);
@endphp

<div class="row">
    {{-- Title --}}
    <div class="col-12 col-md-8 form-group">
        <label class="required">@lang('virtual_classes.field_title')</label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $vc?->title) }}" maxlength="160" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Teacher --}}
    <div class="col-12 col-md-4 form-group">
        <label class="required">@lang('virtual_classes.field_teacher')</label>
        <select name="teacher_id" class="form-control @error('teacher_id') is-invalid @enderror" required>
            <option value="">— @lang('virtual_classes.select_teacher') —</option>
            @foreach($teachers as $t)
            <option value="{{ $t->id }}" {{ (int) $defaultTeacher === $t->id ? 'selected' : '' }}>
                {{ $isRtl && $t->name_ar ? $t->name_ar : $t->name }}
            </option>
            @endforeach
        </select>
        @error('teacher_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Scheduled at --}}
    <div class="col-12 col-md-4 form-group">
        <label class="required">@lang('virtual_classes.field_scheduled_at')</label>
        <input type="datetime-local" name="scheduled_at"
               class="form-control @error('scheduled_at') is-invalid @enderror"
               value="{{ old('scheduled_at', $vc?->scheduled_at?->format('Y-m-d\TH:i')) }}" required>
        @error('scheduled_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Duration --}}
    <div class="col-12 col-md-4 form-group">
        <label class="required">@lang('virtual_classes.field_duration')</label>
        <div class="input-group">
            <input type="number" name="duration_minutes"
                   class="form-control @error('duration_minutes') is-invalid @enderror"
                   value="{{ old('duration_minutes', $vc?->duration_minutes ?? 45) }}"
                   min="10" max="480" required>
            <div class="input-group-append">
                <span class="input-group-text">@lang('virtual_classes.minutes')</span>
            </div>
        </div>
        @error('duration_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Platform --}}
    <div class="col-12 col-md-4 form-group">
        <label class="required">@lang('virtual_classes.field_platform')</label>
        @php $selPlatform = old('platform', $vc?->platform ?? 'zoom'); @endphp
        <select name="platform" id="vcPlatform" class="form-control @error('platform') is-invalid @enderror" required>
            <option value="zoom"     {{ $selPlatform === 'zoom' ? 'selected' : '' }}>Zoom</option>
            <option value="teams"    {{ $selPlatform === 'teams' ? 'selected' : '' }}>Microsoft Teams</option>
            <option value="external" {{ $selPlatform === 'external' ? 'selected' : '' }}>@lang('virtual_classes.platform_external')</option>
            <option value="internal" {{ $selPlatform === 'internal' ? 'selected' : '' }}>@lang('virtual_classes.platform_internal')</option>
        </select>
        @error('platform') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Class --}}
    <div class="col-12 col-md-4 form-group">
        <label>@lang('virtual_classes.field_class')</label>
        <select name="class_id" class="form-control @error('class_id') is-invalid @enderror">
            <option value="">— @lang('virtual_classes.select_class') —</option>
            @foreach(($classes ?? []) as $c)
            <option value="{{ $c->id }}" {{ (int) old('class_id', $vc?->class_id) === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        @error('class_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">@lang('virtual_classes.select_class')</small>
    </div>

    {{-- Subject --}}
    <div class="col-12 col-md-4 form-group">
        <label>@lang('virtual_classes.field_subject')</label>
        <select name="subject_id" class="form-control @error('subject_id') is-invalid @enderror">
            <option value="">— @lang('virtual_classes.select_subject') —</option>
            @foreach(($subjects ?? []) as $s)
            <option value="{{ $s->id }}" {{ (int) old('subject_id', $vc?->subject_id) === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- External URL (for Teams / external link) --}}
    <div class="col-12 form-group" id="vcExternalUrlWrap" style="{{ in_array($selPlatform, ['teams','external']) ? '' : 'display:none' }}">
        <label class="required">@lang('virtual_classes.field_external_url')</label>
        <input type="url" name="external_url" class="form-control @error('external_url') is-invalid @enderror"
               value="{{ old('external_url', $vc?->external_url) }}" maxlength="1000" placeholder="https://...">
        @error('external_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Audience --}}
    <div class="col-12 form-group">
        <label>@lang('virtual_classes.field_audience')</label>
        <div class="d-flex flex-wrap gap-2">
            @foreach($audienceOpts as $val => $label)
            <div class="custom-control custom-checkbox {{ $isRtl ? 'mr-2' : 'ml-0 mr-3' }}">
                <input type="checkbox" class="custom-control-input audience-cb" id="vcAud_{{ $val }}"
                       name="audience[]" value="{{ $val }}"
                       {{ in_array($val, (array) $selectedAudience) ? 'checked' : '' }}>
                <label class="custom-control-label" for="vcAud_{{ $val }}">{{ $label }}</label>
            </div>
            @endforeach
        </div>
        @error('audience') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    {{-- Description --}}
    <div class="col-12 form-group">
        <label>@lang('virtual_classes.field_description')</label>
        <textarea name="description" rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $vc?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('change', '#vcAud_all', function () {
        if ($(this).is(':checked')) {
            $('.audience-cb').not(this).prop('checked', false);
        }
    });
    $(document).on('change', '.audience-cb:not(#vcAud_all)', function () {
        if ($(this).is(':checked')) {
            $('#vcAud_all').prop('checked', false);
        }
    });
    // Show the external-URL field only for Teams / external-link platforms.
    $(document).on('change', '#vcPlatform', function () {
        var needsUrl = ['teams', 'external'].indexOf($(this).val()) !== -1;
        $('#vcExternalUrlWrap').toggle(needsUrl);
    });
});
</script>
@endpush
