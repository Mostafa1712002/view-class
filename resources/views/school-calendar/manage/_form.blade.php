@php
    $isRtl       = app()->getLocale() === 'ar';
    $isSuper     = auth()->user()->isSuperAdmin();
    $eventTypes  = collect(\App\Models\SchoolEvent::TYPES)->mapWithKeys(fn ($t) => [$t => __('school_calendar.type_' . $t)])->all();
    $audienceOpts = ['all' => __('school_calendar.audience_all'), 'students' => __('school_calendar.audience_students'), 'parents' => __('school_calendar.audience_parents'), 'teachers' => __('school_calendar.audience_teachers'), 'staff' => __('school_calendar.audience_staff')];
    $colors = ['#e74c3c' => __('school_calendar.color_red'), '#e67e22' => __('school_calendar.color_orange'), '#f1c40f' => __('school_calendar.color_yellow'), '#2ecc71' => __('school_calendar.color_green'), '#3498db' => __('school_calendar.color_blue'), '#9b59b6' => __('school_calendar.color_purple'), '#95a5a6' => __('school_calendar.color_gray')];

    $isAllDay        = old('all_day', $event ? $event->all_day : true);
    $targetType      = old('target_type', $event->target_type ?? 'school');
    $selectedAudience = old('audience', $event ? ($event->audience ?? ['all']) : ['all']);
    $selectedGrades  = old('grade_levels', $event ? ($event->grade_levels ?? []) : []);
    $selectedClasses = old('class_ids', $event ? ($event->class_ids ?? []) : []);
    $selectedUsers   = old('user_target_ids', $event ? $event->targets->where('kind', 'user')->pluck('target_id')->all() : []);
    $notify          = old('notify', $event->notify ?? false);
    $remindBefore    = old('remind_before', $event->remind_before ?? false);
    $remindMinutes   = old('remind_minutes', $event->remind_minutes ?? 60);

    $schools     = $schools ?? collect();
    $classes     = $classes ?? collect();
    $users       = $users ?? collect();
    $gradeLevels = $gradeLevels ?? range(1, 12);
@endphp

<div class="row">
    {{-- Title --}}
    <div class="col-12 col-md-8 form-group">
        <label class="required">@lang('school_calendar.field_title')</label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $event?->title) }}" maxlength="160" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Event type --}}
    <div class="col-12 col-md-4 form-group">
        <label class="required">@lang('school_calendar.field_type')</label>
        <select name="event_type" class="form-control @error('event_type') is-invalid @enderror" required>
            @foreach($eventTypes as $val => $label)
            <option value="{{ $val }}" {{ old('event_type', $event?->event_type ?? 'general') === $val ? 'selected' : '' }}>
                {{ $label }}
            </option>
            @endforeach
        </select>
        @error('event_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Start date --}}
    <div class="col-12 col-md-4 form-group">
        <label class="required">@lang('school_calendar.field_start_date')</label>
        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
               value="{{ old('start_date', $event?->start_date?->toDateString()) }}" required>
        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- End date --}}
    <div class="col-12 col-md-4 form-group">
        <label>@lang('school_calendar.field_end_date')</label>
        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
               value="{{ old('end_date', $event?->end_date?->toDateString()) }}">
        @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- All-day toggle --}}
    <div class="col-12 col-md-4 form-group d-flex align-items-end">
        <div class="custom-control custom-checkbox mt-1">
            <input type="checkbox" class="custom-control-input" id="all_day" name="all_day" value="1"
                   {{ $isAllDay ? 'checked' : '' }}>
            <label class="custom-control-label" for="all_day">@lang('school_calendar.field_all_day')</label>
        </div>
    </div>

    {{-- Time fields (hidden when all-day) --}}
    <div class="col-12 col-md-3 form-group" id="start-time-wrap" style="{{ $isAllDay ? 'display:none' : '' }}">
        <label>@lang('school_calendar.field_start_time')</label>
        <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror"
               value="{{ old('start_time', $event?->start_time) }}">
        @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-3 form-group" id="end-time-wrap" style="{{ $isAllDay ? 'display:none' : '' }}">
        <label>@lang('school_calendar.field_end_time')</label>
        <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror"
               value="{{ old('end_time', $event?->end_time) }}">
        @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Color --}}
    <div class="col-12 col-md-3 form-group">
        <label>@lang('school_calendar.field_color')</label>
        <select name="color" class="form-control @error('color') is-invalid @enderror">
            <option value="">@lang('school_calendar.color_auto')</option>
            @foreach($colors as $hex => $label)
            <option value="{{ $hex }}" {{ old('color', $event?->color) === $hex ? 'selected' : '' }}>
                {{ $label }}
            </option>
            @endforeach
        </select>
        @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Location --}}
    <div class="col-12 col-md-3 form-group">
        <label>@lang('school_calendar.field_location')</label>
        <input type="text" name="location" class="form-control @error('location') is-invalid @enderror"
               value="{{ old('location', $event?->location) }}" maxlength="160">
        @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- ── Targeting ─────────────────────────────────────────────────────────── --}}
<hr>
<h6 class="mb-2"><x-svg-icon name="people" :size="15" /> @lang('school_calendar.target_heading')</h6>

<div class="row">
    <div class="col-12 form-group">
        <div class="custom-control custom-radio custom-control-inline">
            <input type="radio" class="custom-control-input cal-target-mode" id="tt_school"
                   name="target_type" value="school" {{ $targetType !== 'specific' ? 'checked' : '' }}>
            <label class="custom-control-label" for="tt_school">@lang('school_calendar.target_school')</label>
        </div>
        <div class="custom-control custom-radio custom-control-inline">
            <input type="radio" class="custom-control-input cal-target-mode" id="tt_specific"
                   name="target_type" value="specific" {{ $targetType === 'specific' ? 'checked' : '' }}>
            <label class="custom-control-label" for="tt_specific">@lang('school_calendar.target_specific')</label>
        </div>
        @error('user_target_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Whole-school: audience role groups --}}
    <div class="col-12 form-group cal-scope-school" style="{{ $targetType === 'specific' ? 'display:none' : '' }}">
        <label class="required">@lang('school_calendar.field_audience')</label>
        <div class="d-flex flex-wrap gap-2">
            @foreach($audienceOpts as $val => $label)
            <div class="custom-control custom-checkbox {{ $isRtl ? 'mr-2' : 'ml-0 mr-3' }}">
                <input type="checkbox" class="custom-control-input audience-cb" id="aud_{{ $val }}"
                       name="audience[]" value="{{ $val }}"
                       {{ in_array($val, (array) $selectedAudience) ? 'checked' : '' }}>
                <label class="custom-control-label" for="aud_{{ $val }}">{{ $label }}</label>
            </div>
            @endforeach
        </div>
        @error('audience') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    {{-- Specific targeting: school / grades / classes / users (checkbox grids + select-all) --}}
    <div class="col-12 cal-scope-specific" style="{{ $targetType === 'specific' ? '' : 'display:none' }}">
        @if($isSuper && $schools->count())
        <div class="form-group" style="max-width:360px">
            <label>@lang('school_calendar.field_school')</label>
            <select name="school_id" id="cal-school" class="form-control">
                @foreach($schools as $s)
                <option value="{{ $s->id }}" {{ (int) old('school_id', $event?->school_id) === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">@lang('school_calendar.school_hint')</small>
        </div>
        @endif

        <x-audience-selector
            :grids="['grades', 'classes', 'users']"
            :conditional="false"
            :school-select="($isSuper && $schools->count()) ? 'cal-school' : null"
            :grade-levels="$gradeLevels"
            :classes="$classes"
            :users="$users"
            :selected-grades="$selectedGrades"
            :selected-classes="$selectedClasses"
            :selected-users="$selectedUsers"
        />
    </div>
</div>

{{-- ── Notifications ─────────────────────────────────────────────────────── --}}
<hr>
<h6 class="mb-2"><x-svg-icon name="bell" :size="15" /> @lang('school_calendar.notif_heading')</h6>

<div class="row">
    <div class="col-12 col-md-4 form-group">
        <div class="custom-control custom-checkbox">
            <input type="hidden" name="notify" value="0">
            <input type="checkbox" class="custom-control-input" id="notify" name="notify" value="1" {{ $notify ? 'checked' : '' }}>
            <label class="custom-control-label" for="notify">@lang('school_calendar.field_notify')</label>
        </div>
    </div>

    <div class="col-12 col-md-4 form-group">
        <div class="custom-control custom-checkbox">
            <input type="hidden" name="remind_before" value="0">
            <input type="checkbox" class="custom-control-input" id="remind_before" name="remind_before" value="1" {{ $remindBefore ? 'checked' : '' }}>
            <label class="custom-control-label" for="remind_before">@lang('school_calendar.field_remind')</label>
        </div>
    </div>

    <div class="col-12 col-md-4 form-group" id="remind-minutes-wrap" style="{{ $remindBefore ? '' : 'display:none' }}">
        <label>@lang('school_calendar.field_remind_when')</label>
        <select name="remind_minutes" class="form-control">
            @foreach([15 => 15, 30 => 30, 60 => 60, 120 => 120, 1440 => 1440] as $min => $lbl)
            <option value="{{ $min }}" {{ (int) $remindMinutes === $min ? 'selected' : '' }}>
                @if($min < 60) {{ $min }} @lang('school_calendar.minutes')
                @elseif($min < 1440) {{ intdiv($min, 60) }} @lang('school_calendar.hours')
                @else @lang('school_calendar.one_day') @endif
            </option>
            @endforeach
        </select>
    </div>
</div>

{{-- Description --}}
<div class="row">
    <div class="col-12 form-group">
        <label>@lang('school_calendar.field_description')</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $event?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    // All-day hides the time pickers
    $('#all_day').on('change', function () {
        $('#start-time-wrap, #end-time-wrap').toggle(!$(this).is(':checked'));
    });

    // Audience: "all" is mutually exclusive with the role groups
    $(document).on('change', '#aud_all', function () {
        if ($(this).is(':checked')) { $('.audience-cb').not(this).prop('checked', false); }
    });
    $(document).on('change', '.audience-cb:not(#aud_all)', function () {
        if ($(this).is(':checked')) { $('#aud_all').prop('checked', false); }
    });

    // Target mode switches the scope blocks
    $(document).on('change', '.cal-target-mode', function () {
        var specific = $('#tt_specific').is(':checked');
        $('.cal-scope-specific').toggle(specific);
        $('.cal-scope-school').toggle(!specific);
    });

    // Reminder window appears only when reminder is on
    $('#remind_before').on('change', function () {
        $('#remind-minutes-wrap').toggle($(this).is(':checked'));
    });

    // School-based class/user filtering is handled inside the audience-selector component.
});
</script>
@endpush
