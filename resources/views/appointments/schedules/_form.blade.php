@php
    $isRtl    = app()->getLocale() === 'ar';
    $days     = __('appointments.days');
    $modes    = __('appointments.modes');
    $statuses = ['active' => __('appointments.status_active'), 'inactive' => __('appointments.status_inactive')];
    $user     = auth()->user();
    $isAdmin  = $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());

    // When editing, use model values; on create, use old() or sensible defaults
    $old = fn(string $key, $default = null) => old($key, $schedule?->{$key} ?? $default);
    $oldDays = old('days', $schedule?->days ?? []);
@endphp

<div class="row">
    {{-- Title --}}
    <div class="col-md-12 form-group">
        <label>@lang('appointments.field_title') <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ $old('title') }}" required maxlength="255">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Date range --}}
    <div class="col-md-3 form-group">
        <label>@lang('appointments.field_date_from') <span class="text-danger">*</span></label>
        <input type="date" name="date_from" class="form-control @error('date_from') is-invalid @enderror"
               value="{{ $old('date_from', $schedule?->date_from?->format('Y-m-d')) }}" required>
        @error('date_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 form-group">
        <label>@lang('appointments.field_date_to') <span class="text-danger">*</span></label>
        <input type="date" name="date_to" class="form-control @error('date_to') is-invalid @enderror"
               value="{{ $old('date_to', $schedule?->date_to?->format('Y-m-d')) }}" required>
        @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Time range --}}
    <div class="col-md-3 form-group">
        <label>@lang('appointments.field_time_from') <span class="text-danger">*</span></label>
        <input type="time" name="time_from" class="form-control @error('time_from') is-invalid @enderror"
               value="{{ $old('time_from', is_string($schedule?->time_from) ? \Illuminate\Support\Str::substr($schedule->time_from, 0, 5) : '') }}" required>
        @error('time_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 form-group">
        <label>@lang('appointments.field_time_to') <span class="text-danger">*</span></label>
        <input type="time" name="time_to" class="form-control @error('time_to') is-invalid @enderror"
               value="{{ $old('time_to', is_string($schedule?->time_to) ? \Illuminate\Support\Str::substr($schedule->time_to, 0, 5) : '') }}" required>
        @error('time_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Days of week --}}
    <div class="col-md-12 form-group">
        <label>@lang('appointments.field_days')</label>
        <div class="d-flex flex-wrap gap-2 mt-1">
            @foreach($days as $key => $label)
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="days[]"
                       id="day_{{ $key }}" value="{{ $key }}"
                       @if(in_array($key, (array) $oldDays)) checked @endif>
                <label class="form-check-label" for="day_{{ $key }}">{{ $label }}</label>
            </div>
            @endforeach
        </div>
        @error('days')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    {{-- Slot minutes --}}
    <div class="col-md-3 form-group">
        <label>@lang('appointments.field_slot_minutes') <span class="text-danger">*</span></label>
        <input type="number" name="slot_minutes" class="form-control @error('slot_minutes') is-invalid @enderror"
               value="{{ $old('slot_minutes', 30) }}" min="5" max="480" required>
        @error('slot_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Max appointments --}}
    <div class="col-md-3 form-group">
        <label>@lang('appointments.field_max_appointments')</label>
        <input type="number" name="max_appointments" class="form-control @error('max_appointments') is-invalid @enderror"
               value="{{ $old('max_appointments') }}" min="1" placeholder="{{ __('appointments.slots_unlimited') }}">
        @error('max_appointments')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Mode --}}
    <div class="col-md-3 form-group">
        <label>@lang('appointments.field_mode') <span class="text-danger">*</span></label>
        <select name="mode" class="form-control @error('mode') is-invalid @enderror" required>
            @foreach($modes as $key => $label)
                <option value="{{ $key }}" @selected($old('mode', 'in_person') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Status --}}
    <div class="col-md-3 form-group">
        <label>@lang('appointments.field_status') <span class="text-danger">*</span></label>
        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected($old('status', 'active') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Location --}}
    <div class="col-md-6 form-group">
        <label>@lang('appointments.field_location')</label>
        <input type="text" name="location" class="form-control @error('location') is-invalid @enderror"
               value="{{ $old('location') }}" maxlength="255">
        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Booking open --}}
    <div class="col-md-3 form-group d-flex align-items-end">
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="booking_open" id="booking_open" value="1"
                   @if((bool) $old('booking_open', $schedule?->booking_open ?? true)) checked @endif>
            <label class="form-check-label" for="booking_open">@lang('appointments.field_booking_open')</label>
        </div>
    </div>

    {{-- Notes --}}
    <div class="col-md-12 form-group">
        <label>@lang('appointments.field_notes')</label>
        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ $old('notes') }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
