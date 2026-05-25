@csrf

@php
    // LineAwesome 1.x (theme version) — only classes that exist in this build.
    $iconChoices = [
        'la-bell', 'la-book', 'la-globe', 'la-paint-brush', 'la-search',
        'la-map', 'la-sticky-note', 'la-bus', 'la-calculator',
        'la-flask', 'la-desktop', 'la-television', 'la-cube',
        'la-graduation-cap', 'la-bookmark', 'la-pencil', 'la-apple',
        'la-language', 'la-music', 'la-futbol-o', 'la-laptop', 'la-leaf',
    ];
    $selectedIcon = old('icon', $subject->icon);
@endphp

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
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.short_name_ar')</label>
                <input type="text" name="short_name_ar" class="form-control" value="{{ old('short_name_ar', $subject->short_name_ar) }}" placeholder="رياضيات">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.short_name_en')</label>
                <input type="text" name="short_name_en" class="form-control" value="{{ old('short_name_en', $subject->short_name_en) }}" placeholder="Math">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.language')</label>
                @php $lang = old('language', $subject->language); @endphp
                <select name="language" class="form-control">
                    <option value="" {{ $lang === null || $lang === '' ? 'selected' : '' }}>@lang('sprint4.subjects.form.language_none')</option>
                    <option value="ar" {{ $lang === 'ar' ? 'selected' : '' }}>@lang('sprint4.subjects.form.language_ar')</option>
                    <option value="en" {{ $lang === 'en' ? 'selected' : '' }}>@lang('sprint4.subjects.form.language_en')</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.code')</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $subject->code) }}" placeholder="MATH101">
            </div>
            <div class="col-12 mb-1">
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
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.grade_levels') <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-2">
                    @php $selected = old('grade_levels', $subject->grade_levels ?? []); @endphp
                    @for($i = 1; $i <= 12; $i++)
                        <label class="grade-pick">
                            <input type="checkbox" name="grade_levels[]" value="{{ $i }}" {{ in_array($i, $selected) ? 'checked' : '' }}>
                            <span>{{ $i }}</span>
                        </label>
                    @endfor
                </div>
                <small class="text-muted">اختر صفًا واحدًا على الأقل لربط المادة به.</small>
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
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.total_hours')</label>
                <input type="number" name="total_hours" min="0" max="50" class="form-control" value="{{ old('total_hours', $subject->total_hours) }}" placeholder="0">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.weekly_lessons')</label>
                <input type="number" name="credit_hours" min="0" max="50" class="form-control" value="{{ old('credit_hours', $subject->credit_hours) }}" placeholder="0">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">@lang('sprint4.subjects.form.credit_value')</label>
                <input type="number" name="credit_value" min="0" max="50" class="form-control" value="{{ old('credit_value', $subject->credit_value) }}" placeholder="0">
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_core" value="0">
                    <input type="checkbox" name="is_core" value="1" id="is_core" class="form-check-input" {{ old('is_core', $subject->is_core ?? false) ? 'checked' : '' }}>
                    <label for="is_core" class="form-check-label fw-semibold">@lang('sprint4.subjects.form.is_core')</label>
                </div>
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" {{ old('is_active', $subject->is_active ?? true) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label fw-semibold">@lang('sprint4.subjects.form.is_active')</label>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4. Appearance — icon picker ------------------------------------------- --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="la la-icons" style="color: var(--gold-400);"></i> @lang('sprint4.subjects.sections.appearance')</h5>
    </div>
    <div class="card-body">
        <label class="form-label fw-semibold">@lang('sprint4.subjects.form.icon')</label>
        <input type="hidden" name="icon" id="icon-input" value="{{ $selectedIcon }}">
        <div class="icon-grid">
            @foreach($iconChoices as $icon)
                <button type="button" class="icon-pick {{ $selectedIcon === $icon ? 'is-selected' : '' }}" data-icon="{{ $icon }}" title="{{ $icon }}">
                    <i class="la {{ $icon }}"></i>
                </button>
            @endforeach
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-4">
    <a href="{{ route('admin.subjects.index') }}" class="btn btn-soft">
        <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
        @lang('sprint4.subjects.form.cancel')
    </a>
    <button type="reset" class="btn btn-soft">
        <i class="la la-eraser"></i> @lang('sprint4.subjects.form.reset')
    </button>
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
    body.theme-light .icon-grid {
        display: flex; flex-wrap: wrap; gap: .6rem;
    }
    body.theme-light .icon-pick {
        width: 54px; height: 54px; border-radius: 12px;
        border: 1px solid #e5e7eb; background: #fff; color: #64748b;
        font-size: 1.5rem; cursor: pointer; transition: all .15s ease;
        display: inline-flex; align-items: center; justify-content: center;
    }
    body.theme-light .icon-pick:hover { border-color: var(--gold-300); color: var(--gold-500); transform: translateY(-2px); }
    body.theme-light .icon-pick.is-selected {
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        border-color: var(--gold-400); color: var(--gold-500);
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('icon-input');
    document.querySelectorAll('.icon-pick').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var icon = btn.dataset.icon;
            // Toggle: clicking the selected icon clears it.
            if (input.value === icon) {
                input.value = '';
                btn.classList.remove('is-selected');
                return;
            }
            document.querySelectorAll('.icon-pick').forEach(function (b) { b.classList.remove('is-selected'); });
            btn.classList.add('is-selected');
            input.value = icon;
        });
    });
});
</script>
@endpush
