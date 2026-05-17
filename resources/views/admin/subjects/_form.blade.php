@csrf

{{-- 1. Basic information -------------------------------------------------- --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="la la-info-circle" style="color: var(--gold-400);"></i> @lang('sprint4.subjects.sections.basic')</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.name') <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $subject->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.name_en')</label>
                <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $subject->name_en) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.code')</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $subject->code) }}" placeholder="MATH101">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.description')</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $subject->description) }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- 2. Classification & academic link -------------------------------------- --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="la la-layer-group" style="color: var(--gold-400);"></i> @lang('sprint4.subjects.sections.classification')</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.section')</label>
                <input type="text" name="section" class="form-control" value="{{ old('section', $subject->section) }}" placeholder="عام / علمي / أدبي">
                <small class="text-muted">يمكن تركها فارغة للمواد العامة.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.certificate_order')</label>
                <input type="number" name="certificate_order" min="0" max="9999" class="form-control" value="{{ old('certificate_order', $subject->certificate_order ?? 0) }}">
                <small class="text-muted">يحدد ترتيب ظهور المادة في الشهادة.</small>
            </div>
            <div class="col-12 mb-2">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.grade_levels')</label>
                <div class="d-flex flex-wrap gap-2">
                    @php $selected = old('grade_levels', $subject->grade_levels ?? []); @endphp
                    @for($i = 1; $i <= 12; $i++)
                        <label class="grade-pick">
                            <input type="checkbox" name="grade_levels[]" value="{{ $i }}" {{ in_array($i, $selected) ? 'checked' : '' }}>
                            <span>{{ $i }}</span>
                        </label>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 3. Subject settings --------------------------------------------------- --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="la la-sliders-h" style="color: var(--gold-400);"></i> @lang('sprint4.subjects.sections.settings')</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.credit_hours')</label>
                <input type="number" name="credit_hours" min="0" max="20" class="form-control" value="{{ old('credit_hours', $subject->credit_hours) }}" placeholder="0">
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_core" value="0">
                    <input type="checkbox" name="is_core" value="1" id="is_core" class="form-check-input" {{ old('is_core', $subject->is_core ?? false) ? 'checked' : '' }}>
                    <label for="is_core" class="form-check-label fw-semibold">@lang('sprint4.subjects.form.is_core')</label>
                </div>
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" {{ old('is_active', $subject->is_active ?? true) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label fw-semibold">@lang('sprint4.subjects.form.is_active')</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-4">
    <a href="{{ route('admin.subjects.index') }}" class="btn btn-soft">
        <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
        @lang('sprint4.subjects.form.cancel')
    </a>
    <button type="submit" class="btn add-subject-btn">
        <i class="la la-save"></i> @lang('sprint4.subjects.form.save')
    </button>
</div>

@push('styles')
<style>
    body.theme-light .grade-pick { cursor: pointer; }
    body.theme-light .grade-pick input { display: none; }
    body.theme-light .grade-pick span {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 44px; height: 38px; padding: 0 .75rem;
        border: 1px solid #e5e7eb; background: #fff; border-radius: 10px;
        font-weight: 600; color: #475569; transition: all .15s ease;
    }
    body.theme-light .grade-pick:hover span { border-color: var(--gold-300); color: var(--gold-500); }
    body.theme-light .grade-pick input:checked + span {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-300));
        border-color: var(--gold-400); color: #fff;
        box-shadow: 0 4px 12px rgba(207,160,70,.25);
    }
    body.theme-light .add-subject-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; padding: .55rem 1.25rem;
        border-radius: 10px; font-weight: 600; box-shadow: 0 4px 14px rgba(207,160,70,.25);
    }
    body.theme-light .add-subject-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(207,160,70,.32); }
    body.theme-light .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; padding: .55rem 1.25rem; font-weight: 500;
    }
    body.theme-light .btn-soft:hover { background: #f8fafc; color: #0f172a; }
    body.theme-light .form-switch .form-check-input {
        width: 2.4rem; height: 1.3rem; cursor: pointer;
    }
    body.theme-light .form-switch .form-check-input:checked {
        background-color: var(--gold-400); border-color: var(--gold-400);
    }
</style>
@endpush
