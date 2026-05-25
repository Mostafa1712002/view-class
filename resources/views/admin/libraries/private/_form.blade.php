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

<div class="row">
    <div class="col-md-4 col-12 lib-field">
        <label class="form-label">@lang('libraries.private.fields.classes')</label>
        <select name="audiences[class][ids][]" class="form-select" multiple size="6">
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(in_array($c->id, $existingClasses))>{{ $c->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="audiences[class][type]" value="class" />
    </div>
    <div class="col-md-4 col-12 lib-field">
        <label class="form-label">@lang('libraries.private.fields.students')</label>
        <select name="audiences[user][ids][]" class="form-select" multiple size="6">
            @foreach($students as $s)
                <option value="{{ $s->id }}" @selected(in_array($s->id, $existingStudents))>{{ $s->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="audiences[user][type]" value="user" />
    </div>
    <div class="col-md-4 col-12 lib-field">
        <label class="form-label">@lang('libraries.private.fields.teachers')</label>
        <select name="audiences[teacher][ids][]" class="form-select" multiple size="6">
            @foreach($teachers as $t)
                <option value="{{ $t->id }}" @selected(in_array($t->id, $existingTeachers))>{{ $t->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="audiences[teacher][type]" value="teacher" />
    </div>
</div>

<div class="mt-3 d-flex gap-2 flex-wrap">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('libraries.actions.save')</button>
    <a href="{{ route('admin.libraries.private.index') }}" class="btn btn-outline-secondary">@lang('libraries.actions.cancel')</a>
</div>
