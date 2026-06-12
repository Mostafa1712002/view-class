@extends('layouts.app')

@section('title', __('discussion.page_title_create_room'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('discussion.page_title_create_room')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('discussion.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.discussion-rooms.index') }}">@lang('discussion.breadcrumb_manage')</a></li>
                <li class="breadcrumb-item active">@lang('discussion.page_title_create_room')</li>
            </ol>
        </div>
    </div>
</div>

@include('components.alerts')

<div class="card">
    <div class="card-header">
        <h4 class="card-title">@lang('discussion.page_title_create_room')</h4>
    </div>
    <div class="card-content">
        <div class="card-body">
            <form action="{{ route('manage.discussion-rooms.store') }}" method="POST" id="createRoomForm">
                @csrf

                <div class="form-group">
                    <label for="title">@lang('discussion.field_title') <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" maxlength="160"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title') }}"
                        placeholder="{{ __('discussion.placeholder_title') }}"
                        required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">@lang('discussion.field_description')</label>
                    <textarea name="description" id="description" rows="3"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="{{ __('discussion.placeholder_desc') }}">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="la la-save"></i> @lang('discussion.btn_save')
                    </button>
                    <a href="{{ route('manage.discussion-rooms.index') }}" class="btn btn-secondary ml-1">
                        @lang('discussion.btn_cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
