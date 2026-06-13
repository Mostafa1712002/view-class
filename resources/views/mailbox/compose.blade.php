@extends('layouts.app')

@section('title', __('mailbox.compose'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('mailbox.compose')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('mailbox.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('my.mailbox.index') }}">@lang('mailbox.breadcrumb_mailbox')</a></li>
                <li class="breadcrumb-item active">@lang('mailbox.compose')</li>
            </ol>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">@lang('mailbox.compose')</h4>
    </div>
    <div class="card-content">
        <div class="card-body">
            <form action="{{ route('my.mailbox.store') }}" method="POST"
                  enctype="multipart/form-data" id="composeForm">
                @csrf

                <div class="row">
                    {{-- Subject --}}
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="subject">@lang('mailbox.subject') <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="subject" maxlength="255"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject') }}" required>
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
                                <option value="normal"    @selected(old('importance', 'normal') === 'normal')>@lang('mailbox.normal')</option>
                                <option value="important" @selected(old('importance') === 'important')>@lang('mailbox.important_label')</option>
                                <option value="urgent"    @selected(old('importance') === 'urgent')>@lang('mailbox.urgent')</option>
                            </select>
                            @error('importance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Recipients --}}
                <div class="form-group">
                    <label for="to">@lang('mailbox.recipients') <span class="text-danger">*</span></label>
                    <select name="to[]" id="to" multiple
                            class="form-control select2-recipients @error('to') is-invalid @enderror @error('to.*') is-invalid @enderror">
                        @foreach($recipients as $recipient)
                            <option value="{{ $recipient->id }}"
                                @if(in_array($recipient->id, (array) old('to', []))) selected @endif>
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
                                    @selected(old('related_student_id') == $child->id)>
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
                              required>{{ old('body') }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Attachment --}}
                <div class="form-group">
                    <label for="attachment">@lang('mailbox.attachment')</label>
                    <input type="file" name="attachment" id="attachment"
                           class="form-control-file @error('attachment') is-invalid @enderror">
                    @error('attachment')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">{{ __('Max 10 MB') }}</small>
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
    if (window.$ && $.fn.select2) {
        $('#to').select2({
            placeholder: '{{ __('mailbox.select_recipients') }}',
            allowClear: true,
            dir: '{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}'
        });
    }
});
</script>
@endpush
