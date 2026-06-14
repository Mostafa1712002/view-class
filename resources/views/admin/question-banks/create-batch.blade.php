@extends('layouts.app')

@section('title', __('question_banks.batch_create_title'))
@section('body_class', 'theme-light')

@push('styles')
@include('admin.question-banks._form_styles')
<style>
.qb-batch-subject-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}
.qb-batch-subject-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: border-color .15s, background .15s;
    user-select: none;
}
.qb-batch-subject-chip input { display: none; }
.qb-batch-subject-chip:has(input:checked) {
    border-color: #c8a84b;
    background: #fdf8ed;
    font-weight: 600;
}
.qb-preview-banner {
    padding: 12px 18px;
    background: #eefaf4;
    border: 1px solid #a7f3d0;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
    color: #065f46;
    display: none;
}
.qb-preview-banner.visible { display: block; }
.qb-select-all-row {
    margin-bottom: 8px;
    font-size: 0.82rem;
}
.qb-select-all-row a { cursor: pointer; text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="content-header row qb-header">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('question_banks.batch_create_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}">@lang('question_banks.breadcrumb_home')</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.question-banks.index') }}">@lang('question_banks.page_title')</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.question-banks.create') }}">@lang('question_banks.create_title')</a>
            </li>
            <li class="breadcrumb-item active">@lang('question_banks.batch_create_title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-3 col-12 text-md-right d-flex align-items-start justify-content-md-end pt-1">
        <a href="{{ route('admin.question-banks.create') }}" class="btn-reset">
            <i class="la la-arrow-right"></i> @lang('question_banks.form.cancel')
        </a>
    </div>
</div>

<div class="content-body">
    <div class="qb-form-wrap">
        <form action="{{ route('admin.question-banks.batch.store') }}" method="POST"
              id="qb-batch-form" autocomplete="off">
            @csrf

            {{-- Preview count banner --}}
            <div class="qb-preview-banner" id="qb-preview-banner">
                <i class="la la-info-circle"></i>
                <span id="qb-preview-text"></span>
            </div>

            @if($errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Basic settings --}}
            <div class="qb-form-section">
                <h3 class="qb-form-section__title">@lang('question_banks.form.section_basic')</h3>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('question_banks.form.visibility') <span class="text-danger">*</span></label>
                        <select name="visibility"
                                class="form-control @error('visibility') is-invalid @enderror"
                                required>
                            @foreach($visibilities as $k => $label)
                                <option value="{{ $k }}"
                                        @selected(old('visibility', 'private') === $k)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('visibility')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('question_banks.form.status') <span class="text-danger">*</span></label>
                        <select name="status"
                                class="form-control @error('status') is-invalid @enderror"
                                required>
                            @foreach($statuses as $k => $label)
                                <option value="{{ $k }}"
                                        @selected(old('status', 'active') === $k)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('question_banks.form.source') <span class="text-danger">*</span></label>
                        <select name="source"
                                class="form-control @error('source') is-invalid @enderror"
                                required>
                            {{-- Exclude legacy ana_qudurat from batch create --}}
                            @foreach(array_filter($sources, fn($k) => $k !== 'ana_qudurat', ARRAY_FILTER_USE_KEY) as $k => $label)
                                <option value="{{ $k }}"
                                        @selected(old('source', 'manual') === $k)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('source')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Grade & Term --}}
            <div class="qb-form-section">
                <h3 class="qb-form-section__title">@lang('question_banks.form.section_education')</h3>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('question_banks.batch_grade')</label>
                        <select name="grade_level"
                                class="form-control @error('grade_level') is-invalid @enderror">
                            <option value="">@lang('question_banks.grade_any')</option>
                            @foreach($grades as $g => $label)
                                <option value="{{ $g }}"
                                        @selected((string) old('grade_level') === (string) $g)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('grade_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('question_banks.batch_term')</label>
                        <input type="text" name="term" value="{{ old('term') }}"
                               class="form-control @error('term') is-invalid @enderror"
                               placeholder="@lang('question_banks.batch_term_placeholder')">
                        @error('term')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Multi-subject selection --}}
            <div class="qb-form-section">
                <h3 class="qb-form-section__title">@lang('question_banks.batch_subjects') <span class="text-danger">*</span></h3>
                @error('subject_ids')
                    <div class="alert alert-danger py-2 mb-2">{{ $message }}</div>
                @enderror
                @if($subjects->isEmpty())
                    <p class="text-muted small">— @lang('question_banks.empty_filtered') —</p>
                @else
                    <div class="qb-select-all-row text-muted">
                        <a id="qb-select-all">تحديد الكل</a> ·
                        <a id="qb-select-none">إلغاء التحديد</a>
                    </div>
                    <div class="qb-batch-subject-grid" id="qb-subject-grid">
                        @foreach($subjects as $subject)
                            <label class="qb-batch-subject-chip">
                                <input type="checkbox"
                                       name="subject_ids[]"
                                       value="{{ $subject->id }}"
                                       class="qb-subject-check"
                                       {{ in_array($subject->id, old('subject_ids', [])) ? 'checked' : '' }}>
                                <span>{{ $subject->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Skip existing toggle --}}
            <div class="qb-form-section">
                <div class="row">
                    <div class="col-12">
                        <label class="qb-toggle">
                            <input type="hidden" name="skip_existing" value="0">
                            <input type="checkbox" name="skip_existing" value="1"
                                   checked id="qb-skip-existing">
                            <span>@lang('question_banks.batch_skip_existing')</span>
                        </label>
                        <div class="alert alert-warning py-2 mt-2 mb-0 small">
                            <i class="la la-info-circle"></i>
                            @lang('question_banks.batch_duplicate_warning')
                        </div>
                    </div>
                </div>
            </div>

            <div class="qb-form-actions">
                <a href="{{ route('admin.question-banks.create') }}" class="btn-reset">
                    @lang('question_banks.form.cancel')
                </a>
                <button type="submit" class="btn-gold" id="qb-batch-submit">
                    <i class="la la-save"></i> @lang('question_banks.batch_submit')
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var checks   = document.querySelectorAll('.qb-subject-check');
    var banner   = document.getElementById('qb-preview-banner');
    var preText  = document.getElementById('qb-preview-text');
    var selAll   = document.getElementById('qb-select-all');
    var selNone  = document.getElementById('qb-select-none');
    var singleMsg = @json(__('question_banks.batch_preview_single'));
    var multiMsg  = @json(__('question_banks.batch_preview_count'));

    function updatePreview() {
        var count = document.querySelectorAll('.qb-subject-check:checked').length;
        if (count === 0) {
            banner.classList.remove('visible');
            return;
        }
        banner.classList.add('visible');
        preText.textContent = count === 1
            ? singleMsg
            : multiMsg.replace(':count', count);
    }

    checks.forEach(function (c) { c.addEventListener('change', updatePreview); });
    updatePreview();

    if (selAll) {
        selAll.addEventListener('click', function (e) {
            e.preventDefault();
            checks.forEach(function (c) { c.checked = true; });
            updatePreview();
        });
    }
    if (selNone) {
        selNone.addEventListener('click', function (e) {
            e.preventDefault();
            checks.forEach(function (c) { c.checked = false; });
            updatePreview();
        });
    }
})();
</script>
@endsection
