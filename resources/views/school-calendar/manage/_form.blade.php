@php
    $isRtl       = app()->getLocale() === 'ar';
    $eventTypes  = ['holiday' => __('school_calendar.type_holiday'), 'exam' => __('school_calendar.type_exam'), 'activity' => __('school_calendar.type_activity'), 'meeting' => __('school_calendar.type_meeting'), 'other' => __('school_calendar.type_other')];
    $audienceOpts = ['all' => __('school_calendar.audience_all'), 'students' => __('school_calendar.audience_students'), 'parents' => __('school_calendar.audience_parents'), 'teachers' => __('school_calendar.audience_teachers'), 'staff' => __('school_calendar.audience_staff')];
    $colors = ['#e74c3c' => __('school_calendar.color_red'), '#e67e22' => __('school_calendar.color_orange'), '#f1c40f' => __('school_calendar.color_yellow'), '#2ecc71' => __('school_calendar.color_green'), '#3498db' => __('school_calendar.color_blue'), '#9b59b6' => __('school_calendar.color_purple'), '#95a5a6' => __('school_calendar.color_gray')];
    $selectedAudience = old('audience', $event ? ($event->audience ?? ['all']) : ['all']);
    $isAllDay = old('all_day', $event ? $event->all_day : true);
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
            <option value="{{ $val }}" {{ old('event_type', $event?->event_type ?? 'other') === $val ? 'selected' : '' }}>
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

    {{-- Audience --}}
    <div class="col-12 form-group">
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

    {{-- Description --}}
    <div class="col-12 form-group">
        <label>@lang('school_calendar.field_description')</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $event?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#all_day').on('change', function () {
        if ($(this).is(':checked')) {
            $('#start-time-wrap, #end-time-wrap').hide();
        } else {
            $('#start-time-wrap, #end-time-wrap').show();
        }
    });

    // If "all" audience is checked, uncheck others; if any other is checked, uncheck "all"
    $(document).on('change', '#aud_all', function () {
        if ($(this).is(':checked')) {
            $('.audience-cb').not(this).prop('checked', false);
        }
    });
    $(document).on('change', '.audience-cb:not(#aud_all)', function () {
        if ($(this).is(':checked')) {
            $('#aud_all').prop('checked', false);
        }
    });
});
</script>
@endpush
