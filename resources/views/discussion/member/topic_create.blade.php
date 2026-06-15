@extends('layouts.app')

@section('title', __('discussion.page_title_new_topic'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('discussion.page_title_new_topic')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('discussion.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('discussion.index') }}">@lang('discussion.breadcrumb_rooms')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('discussion.room', $room->id) }}">{{ $room->title }}</a></li>
                <li class="breadcrumb-item active">@lang('discussion.page_title_new_topic')</li>
            </ol>
        </div>
    </div>
</div>


<div class="ds-card">
    <div class="ds-card-header">
        <span class="ds-card-title"><x-svg-icon name="chat-square-dots" :size="16" /> @lang('discussion.page_title_new_topic')</span>
    </div>
    <div class="ds-card-body">
        @if($room->instructions)
            <div class="alert alert-info">
                <x-svg-icon name="info-circle" :size="15" /> {{ $room->instructions }}
            </div>
        @endif
        <form action="{{ route('discussion.topic.store', $room->id) }}" method="POST" id="topicForm">
            @csrf

            <div class="form-group">
                <label for="title">@lang('discussion.field_title') <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" maxlength="200"
                    class="form-control @error('title') is-invalid @enderror"
                    value="{{ old('title') }}"
                    placeholder="{{ __('discussion.placeholder_topic_title') }}"
                    required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="body">@lang('discussion.field_body') <span class="text-danger">*</span></label>
                <textarea name="body" id="body" rows="6"
                    class="form-control @error('body') is-invalid @enderror"
                    placeholder="{{ __('discussion.placeholder_body') }}"
                    required>{{ old('body') }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <x-svg-icon name="send" :size="15" /> @lang('discussion.btn_save')
                </button>
                <a href="{{ route('discussion.room', $room->id) }}" class="btn btn-outline-secondary ml-1">
                    @lang('discussion.btn_cancel')
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('topicForm');
    var btn  = document.getElementById('submitBtn');
    if (form && btn) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            window.vcConfirm({ title: '{{ __("discussion.confirm_submit") }}' }).then(function (r) {
                if (r.isConfirmed) { form.submit(); }
            });
        });
    }
});
</script>
@endpush
