@php
    /** @var \App\Models\ClassVisit|null $visit */
    $isEdit  = isset($visit) && $visit;
    $action  = $isEdit ? route('admin.class-visits.update', $visit->id) : route('admin.class-visits.store');
    $val     = fn ($key, $default = null) => old($key, $isEdit ? data_get($visit, $key) : $default);
    $vType   = old('visit_type', $isEdit ? $visit->visit_type : 'announced');
@endphp

<form action="{{ $action }}" method="POST" class="card p-3">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        @if ($schools->count())
            <div class="col-md-4">
                <label class="form-label">@lang('class_visits.form.school')</label>
                <select name="school_id" class="form-control">
                    <option value="">@lang('class_visits.form.select')</option>
                    @foreach ($schools as $s)
                        <option value="{{ $s->id }}" {{ (string) $val('school_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.teacher') <span class="text-danger">*</span></label>
            <select name="teacher_id" id="cv-teacher" class="form-control cv-select2" required>
                <option value="">@lang('class_visits.form.select')</option>
                @foreach ($teachers as $t)
                    <option value="{{ $t->id }}" {{ (string) $val('teacher_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.subject')</label>
            <select name="subject_id" class="form-control cv-select2">
                <option value="">@lang('class_visits.form.select')</option>
                @foreach ($subjects as $sub)
                    <option value="{{ $sub->id }}" {{ (string) $val('subject_id') === (string) $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.section')</label>
            <select name="section_id" class="form-control">
                <option value="">@lang('class_visits.form.select')</option>
                @foreach ($sections as $sec)
                    <option value="{{ $sec->id }}" {{ (string) $val('section_id') === (string) $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.class')</label>
            <select name="class_room_id" class="form-control">
                <option value="">@lang('class_visits.form.select')</option>
                @foreach ($classes as $c)
                    <option value="{{ $c->id }}" {{ (string) $val('class_room_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.period')</label>
            <select name="period_id" id="cv-period" class="form-control" data-selected="{{ $val('period_id') }}">
                <option value="">@lang('class_visits.form.period_none')</option>
                @foreach ($periods as $p)
                    <option value="{{ $p->id }}" {{ (string) $val('period_id') === (string) $p->id ? 'selected' : '' }}>
                        {{ ($p->subject?->name ? $p->subject->name.' — ' : '') . ($p->day_name ?: '') . ' / ' . __('class_visits.columns.period') . ' ' . $p->period_number }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">@lang('class_visits.form.period_hint')</small>
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.visit_date') <span class="text-danger">*</span></label>
            <input type="date" name="visit_date" value="{{ $val('visit_date') ? \Illuminate\Support\Str::of((string) $val('visit_date'))->substr(0,10) : '' }}" class="form-control" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.visit_time')</label>
            <input type="time" name="visit_time" value="{{ $val('visit_time') ? \Illuminate\Support\Str::of((string) $val('visit_time'))->substr(0,5) : '' }}" class="form-control">
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.evaluation_form') <span class="text-danger">*</span></label>
            <select name="form_id" class="form-control cv-select2" required>
                <option value="">@lang('class_visits.form.select')</option>
                @foreach ($forms as $f)
                    <option value="{{ $f->id }}" {{ (string) $val('form_id') === (string) $f->id ? 'selected' : '' }}>{{ $f->title }}</option>
                @endforeach
            </select>
            <small class="text-muted">@lang('class_visits.form.form_hint')</small>
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('class_visits.form.visit_type') <span class="text-danger">*</span></label>
            <select name="visit_type" id="cv-type" class="form-control" required>
                @foreach ($types as $tv => $tl)
                    <option value="{{ $tv }}" {{ $vType === $tv ? 'selected' : '' }}>{{ $tl }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 d-flex align-items-center" id="cv-notify-wrap">
            <div class="form-check mt-4">
                <input type="hidden" name="notify_teacher" value="0">
                <input type="checkbox" name="notify_teacher" id="cv-notify" value="1" class="form-check-input"
                       {{ $val('notify_teacher', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="cv-notify">@lang('class_visits.form.notify_teacher')</label>
                <div><small class="text-muted">@lang('class_visits.form.notify_hint')</small></div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">@lang('class_visits.form.pre_notes')</label>
            <textarea name="pre_notes" rows="3" class="form-control">{{ $val('pre_notes') }}</textarea>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn cv-add-btn"><i class="la la-save"></i> @lang('class_visits.form.save')</button>
        <a href="{{ route('admin.class-visits.index') }}" class="btn btn-outline-secondary">@lang('class_visits.form.cancel')</a>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.cv-select2').select2({ width: '100%' });
        }

        // Secret visit disables notify-teacher.
        var typeEl = document.getElementById('cv-type');
        var notify = document.getElementById('cv-notify');
        function syncNotify() {
            if (!typeEl || !notify) return;
            if (typeEl.value === 'secret') {
                notify.checked = false;
                notify.disabled = true;
            } else {
                notify.disabled = false;
            }
        }
        if (typeEl) { typeEl.addEventListener('change', syncNotify); syncNotify(); }
    });
</script>
@endpush
