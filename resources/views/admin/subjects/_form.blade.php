@csrf
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">@lang('sprint4.subjects.form.name') <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $subject->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">@lang('sprint4.subjects.form.name_en')</label>
        <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $subject->name_en) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">@lang('sprint4.subjects.form.code')</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $subject->code) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">@lang('sprint4.subjects.form.section')</label>
        <input type="text" name="section" class="form-control" value="{{ old('section', $subject->section) }}" placeholder="علمي / أدبي">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">@lang('sprint4.subjects.form.credit_hours')</label>
        <input type="number" name="credit_hours" min="0" max="20" class="form-control" value="{{ old('credit_hours', $subject->credit_hours) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">@lang('sprint4.subjects.form.certificate_order')</label>
        <input type="number" name="certificate_order" min="0" max="9999" class="form-control" value="{{ old('certificate_order', $subject->certificate_order ?? 0) }}">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">@lang('sprint4.subjects.form.grade_levels')</label>
        <div class="d-flex flex-wrap gap-2">
            @php $selected = old('grade_levels', $subject->grade_levels ?? []); @endphp
            @for($i = 1; $i <= 12; $i++)
                <label class="me-2">
                    <input type="checkbox" name="grade_levels[]" value="{{ $i }}" {{ in_array($i, $selected) ? 'checked' : '' }}>
                    {{ $i }}
                </label>
            @endfor
        </div>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">@lang('sprint4.subjects.form.description')</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $subject->description) }}</textarea>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-check">
            <input type="hidden" name="is_core" value="0">
            <input type="checkbox" name="is_core" value="1" id="is_core" class="form-check-input" {{ old('is_core', $subject->is_core ?? false) ? 'checked' : '' }}>
            <label for="is_core" class="form-check-label">@lang('sprint4.subjects.form.is_core')</label>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" {{ old('is_active', $subject->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label">@lang('sprint4.subjects.form.is_active')</label>
        </div>
    </div>
</div>
<div class="text-end mt-3">
    <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">@lang('sprint4.subjects.form.cancel')</a>
    <button type="submit" class="btn btn-primary">@lang('sprint4.subjects.form.save')</button>
</div>
