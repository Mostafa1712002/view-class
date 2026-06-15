@extends('layouts.app')

@section('title', __('mailbox.compose'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';

    $isEdit = isset($draft) && $draft;
    $prefill = $prefill ?? [];
    $selectedRecipients = $selectedRecipients ?? [];

    // Resolve old() → prefill → draft for each field, in that precedence.
    $valSubject    = old('subject', $prefill['subject'] ?? ($isEdit ? $draft->subject : ''));
    $valImportance = old('importance', $prefill['importance'] ?? ($isEdit ? $draft->importance : 'normal'));
    $valBody       = old('body', $prefill['body'] ?? ($isEdit ? $draft->body : ''));

    $valTo = old('to', ! empty($selectedRecipients) ? $selectedRecipients : ($prefill['to'] ?? []));
    $valTo = array_map('intval', (array) $valTo);

    $formAction = $isEdit ? route('my.mailbox.update', $draft->id) : route('my.mailbox.store');
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $isEdit ? __('mailbox.edit_draft') : __('mailbox.compose') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('mailbox.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('my.mailbox.index') }}">@lang('mailbox.breadcrumb_mailbox')</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? __('mailbox.edit_draft') : __('mailbox.compose') }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $isEdit ? __('mailbox.edit_draft') : __('mailbox.compose') }}</h4>
    </div>
    <div class="card-content">
        <div class="card-body">
            <form action="{{ $formAction }}" method="POST"
                  enctype="multipart/form-data" id="composeForm">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="row">
                    {{-- Subject --}}
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="subject">@lang('mailbox.subject') <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="subject" maxlength="255"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ $valSubject }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Importance --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="importance">@lang('mailbox.importance') <span class="text-danger">*</span></label>
                            <select name="importance" id="importance"
                                    class="form-control @error('importance') is-invalid @enderror" required>
                                <option value="normal"    @selected($valImportance === 'normal')>@lang('mailbox.normal')</option>
                                <option value="important" @selected($valImportance === 'important')>@lang('mailbox.important_label')</option>
                                <option value="urgent"    @selected($valImportance === 'urgent')>@lang('mailbox.urgent')</option>
                            </select>
                            @error('importance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Recipient group quick-select (card: اختيار مجموعة) --}}
                @if(! empty($roleGroups))
                    <div class="form-group">
                        <label class="d-block">@lang('mailbox.send_to_group')</label>
                        <div class="d-flex flex-wrap" style="gap:6px;">
                            @foreach($roleGroups as $slug => $group)
                                <button type="button"
                                        class="btn btn-sm btn-outline-info js-recipient-group"
                                        data-ids="{{ implode(',', $group['ids']) }}">
                                    <x-svg-icon name="people-fill" :size="14" /> {{ $group['label'] }}
                                    <span class="badge badge-light">{{ count($group['ids']) }}</span>
                                </button>
                            @endforeach
                            <button type="button" class="btn btn-sm btn-outline-secondary js-recipient-clear">
                                <x-svg-icon name="x-circle-fill" :size="14" /> @lang('mailbox.clear_selection')
                            </button>
                        </div>
                        <small class="text-muted">@lang('mailbox.group_hint')</small>
                    </div>
                @endif

                {{-- Recipients (مستخدمون محددون) --}}
                <div class="form-group">
                    <label for="to">@lang('mailbox.recipients') <span class="text-danger">*</span></label>
                    <select name="to[]" id="to" multiple
                            class="form-control select2-recipients @error('to') is-invalid @enderror @error('to.*') is-invalid @enderror">
                        @foreach($recipients as $recipient)
                            <option value="{{ $recipient->id }}"
                                @if(in_array((int) $recipient->id, $valTo, true)) selected @endif>
                                {{ $recipient->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('to')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('to.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Related Student (parents only) --}}
                @if($children->isNotEmpty())
                    <div class="form-group">
                        <label for="related_student_id">@lang('mailbox.related_student')</label>
                        <select name="related_student_id" id="related_student_id"
                                class="form-control @error('related_student_id') is-invalid @enderror">
                            <option value="">— @lang('mailbox.select_student') —</option>
                            @foreach($children as $child)
                                <option value="{{ $child->id }}"
                                    @selected(old('related_student_id', $isEdit ? $draft->related_student_id : null) == $child->id)>
                                    {{ $child->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('related_student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                {{-- Body --}}
                <div class="form-group">
                    <label for="body">@lang('mailbox.body') <span class="text-danger">*</span></label>
                    <textarea name="body" id="body" rows="8"
                              class="form-control @error('body') is-invalid @enderror"
                              required>{{ $valBody }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Attachment --}}
                <div class="form-group">
                    <label for="attachment">@lang('mailbox.attachment')</label>
                    @if($isEdit && $draft->attachment_path)
                        <p class="mb-1">
                            <x-svg-icon name="paperclip" :size="14" class="ic-muted" />
                            <span class="text-muted small">{{ basename($draft->attachment_path) }}</span>
                            <span class="text-muted small">— @lang('mailbox.replace_attachment_hint')</span>
                        </p>
                    @endif
                    <input type="file" name="attachment" id="attachment"
                           class="form-control-file @error('attachment') is-invalid @enderror">
                    @error('attachment')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">@lang('mailbox.attachment_hint')</small>
                </div>

                <div class="form-actions">
                    <button type="submit" name="action" value="send" class="btn btn-primary">
                        <i class="la la-paper-plane"></i> @lang('mailbox.send')
                    </button>
                    <button type="submit" name="action" value="draft" class="btn btn-secondary mx-1">
                        <i class="la la-save"></i> @lang('mailbox.save_draft')
                    </button>
                    <a href="{{ route('my.mailbox.index') }}" class="btn btn-light mx-1">
                        <i class="la la-arrow-left"></i> @lang('mailbox.back')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var $to = window.$ ? $('#to') : null;

    if (window.$ && $.fn.select2) {
        $to.select2({
            placeholder: '{{ __('mailbox.select_recipients') }}',
            allowClear: true,
            dir: '{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}'
        });
    }

    function setSelection(ids, append) {
        var el = document.getElementById('to');
        if (!el) return;
        var current = append
            ? Array.from(el.selectedOptions).map(function (o) { return o.value; })
            : [];
        var next = current.concat(ids.map(String));
        Array.prototype.forEach.call(el.options, function (opt) {
            opt.selected = next.indexOf(opt.value) !== -1;
        });
        if ($to && $to.trigger) { $to.trigger('change'); }
    }

    // Group buttons add that role's users to the current selection.
    document.querySelectorAll('.js-recipient-group').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var raw = (btn.getAttribute('data-ids') || '').split(',').filter(Boolean);
            setSelection(raw, true);
        });
    });

    var clearBtn = document.querySelector('.js-recipient-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () { setSelection([], false); });
    }
});
</script>
@endpush
