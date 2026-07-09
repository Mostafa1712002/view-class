@extends('layouts.app')

@section('title', $survey ? __('surveys.edit_title') : __('surveys.create_title'))
@section('body_class', 'theme-light')

@php
    $isRtl     = app()->getLocale() === 'ar';
    $isEdit    = (bool) $survey;
    $action    = $isEdit ? route('admin.surveys.update', $survey->id) : route('admin.surveys.store');
    $questions = $isEdit ? $survey->questions->toArray() : [];
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $isEdit ? __('surveys.edit_title') : __('surveys.create_title') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('surveys.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.surveys.index') }}">@lang('surveys.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? __('surveys.breadcrumb_edit') : __('surveys.breadcrumb_create') }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-content">
        <div class="card-body">
            <form method="POST" action="{{ $action }}" id="survey-form">
                @csrf
                @if($isEdit) @method('PUT') @endif

                {{-- ── Basic info ────────────────────────────────────────────────── --}}
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="title">@lang('surveys.fields.title') <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $survey?->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group col-md-4">
                        <label for="status">@lang('surveys.fields.status') <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-control @error('status') is-invalid @enderror">
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" @selected(old('status', $survey?->status ?? 'draft') === $s)>
                                    {{ __('surveys.statuses.' . $s) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">@lang('surveys.fields.description')</label>
                    <textarea id="description" name="description"
                              class="form-control @error('description') is-invalid @enderror"
                              rows="3">{{ old('description', $survey?->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="audience">@lang('surveys.fields.audience') <span class="text-danger">*</span></label>
                        <select id="audience" name="audience" class="form-control @error('audience') is-invalid @enderror">
                            @foreach($audiences as $a)
                                <option value="{{ $a }}" @selected(old('audience', $survey?->audience ?? 'all') === $a)>
                                    {{ __('surveys.audiences.' . $a) }}
                                </option>
                            @endforeach
                        </select>
                        @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="starts_at">@lang('surveys.fields.starts_at')</label>
                        <input type="date" id="starts_at" name="starts_at"
                               class="form-control @error('starts_at') is-invalid @enderror"
                               value="{{ old('starts_at', $survey?->starts_at?->format('Y-m-d')) }}">
                        @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="ends_at">@lang('surveys.fields.ends_at')</label>
                        <input type="date" id="ends_at" name="ends_at"
                               class="form-control @error('ends_at') is-invalid @enderror"
                               value="{{ old('ends_at', $survey?->ends_at?->format('Y-m-d')) }}">
                        @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ── Questions builder ──────────────────────────────────────────── --}}
                <hr>
                <h5>@lang('surveys.fields.questions')</h5>
                @error('questions')<div class="alert alert-danger py-1">{{ $message }}</div>@enderror

                <div id="questions-container">
                    {{-- Rendered by JS from #question-template --}}
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="add-question-btn">
                    @lang('surveys.add_question')
                </button>

                <hr>
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('surveys.actions.save')
                    </button>
                    <a href="{{ route('admin.surveys.index') }}" class="btn btn-secondary ml-1">
                        @lang('common.cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Question template (hidden, cloned by JS) --}}
<script type="text/template" id="question-template">
<div class="question-block card border mb-2" data-index="__IDX__">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-1 justify-content-between align-items-center mb-1">
            <strong>@lang('surveys.fields.question_text') #<span class="q-num">__NUM__</span></strong>
            <button type="button" class="btn btn-sm btn-link text-danger remove-question-btn">
                @lang('surveys.remove_question')
            </button>
        </div>
        <div class="form-row">
            <div class="form-group col-md-8 mb-1">
                <input type="text" name="questions[__IDX__][text]"
                       class="form-control form-control-sm"
                       placeholder="@lang('surveys.fields.question_text')" required>
            </div>
            <div class="form-group col-md-3 mb-1">
                <select name="questions[__IDX__][type]" class="form-control form-control-sm question-type-select">
                    <option value="single_choice">@lang('surveys.question_types.single_choice')</option>
                    <option value="multiple_choice">@lang('surveys.question_types.multiple_choice')</option>
                    <option value="text">@lang('surveys.question_types.text')</option>
                    <option value="rating">@lang('surveys.question_types.rating')</option>
                </select>
            </div>
            <div class="form-group col-md-1 mb-1 d-flex align-items-center">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="questions[__IDX__][is_required]"
                           id="req-__IDX__" value="1" checked>
                    <label class="custom-control-label" for="req-__IDX__">@lang('surveys.fields.required')</label>
                </div>
            </div>
        </div>
        <div class="options-area" style="display:none;">
            <div class="options-list mb-1"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary add-option-btn">
                @lang('surveys.add_option')
            </button>
        </div>
    </div>
</div>
</script>

@push('scripts')
<script>
(function () {
    var container = document.getElementById('questions-container');
    var addBtn    = document.getElementById('add-question-btn');
    var template  = document.getElementById('question-template').innerHTML;
    var idx       = 0;

    // Seed existing questions from server
    var existing = @json($questions);
    existing.forEach(function (q) {
        addQuestion(q);
    });

    addBtn.addEventListener('click', function () {
        addQuestion(null);
    });

    function addQuestion(data) {
        var i   = idx++;
        var num = container.children.length + 1;
        var html = template.replace(/__IDX__/g, i).replace(/__NUM__/g, num);

        var tmp = document.createElement('div');
        tmp.innerHTML = html.trim();
        var block = tmp.firstChild;
        container.appendChild(block);

        var typeSelect  = block.querySelector('.question-type-select');
        var optionsArea = block.querySelector('.options-area');
        var optionsList = block.querySelector('.options-list');
        var addOptBtn   = block.querySelector('.add-option-btn');
        var removeBtn   = block.querySelector('.remove-question-btn');

        // Pre-fill from server data
        if (data) {
            block.querySelector('input[name$="[text]"]').value = data.text || '';
            typeSelect.value = data.type || 'single_choice';
            if (data.is_required == false) {
                block.querySelector('input[type=checkbox]').checked = false;
            }
            if (Array.isArray(data.options) && data.options.length) {
                data.options.forEach(function (opt) {
                    addOption(i, optionsList, opt);
                });
            }
        }

        toggleOptionsArea(typeSelect.value, optionsArea);

        typeSelect.addEventListener('change', function () {
            toggleOptionsArea(this.value, optionsArea);
        });

        addOptBtn.addEventListener('click', function () {
            addOption(i, optionsList, '');
        });

        removeBtn.addEventListener('click', function () {
            block.parentNode.removeChild(block);
            renumberQuestions();
        });
    }

    function toggleOptionsArea(type, area) {
        if (type === 'single_choice' || type === 'multiple_choice') {
            area.style.display = '';
        } else {
            area.style.display = 'none';
        }
    }

    function addOption(qIdx, list, value) {
        var row = document.createElement('div');
        row.className = 'd-flex align-items-center mb-1';
        var optIdx = list.children.length;
        row.innerHTML =
            '<input type="text" name="questions[' + qIdx + '][options][' + optIdx + ']"' +
            '       class="form-control form-control-sm mr-1" value="' + escapeHtml(value) + '">' +
            '<button type="button" class="btn btn-sm btn-link text-danger remove-opt-btn">' +
            '@lang("surveys.remove_option")</button>';
        row.querySelector('.remove-opt-btn').addEventListener('click', function () {
            list.removeChild(row);
        });
        list.appendChild(row);
    }

    function renumberQuestions() {
        Array.from(container.children).forEach(function (block, n) {
            var span = block.querySelector('.q-num');
            if (span) span.textContent = n + 1;
        });
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
}());
</script>
@endpush
@endsection
