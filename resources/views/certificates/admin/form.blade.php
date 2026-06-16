@extends('layouts.app')

@section('title', $certificate ? __('certificates.breadcrumb_edit') : __('certificates.breadcrumb_create'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $isEdit = (bool) $certificate;
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $isEdit ? __('certificates.breadcrumb_edit') : __('certificates.breadcrumb_create') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('certificates.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.certificates.index') }}">@lang('certificates.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? __('certificates.breadcrumb_edit') : __('certificates.breadcrumb_create') }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-content">
        <div class="card-body">
            <form action="{{ $isEdit ? route('admin.certificates.update', $certificate->id) : route('admin.certificates.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @if($isEdit) @method('PUT') @endif

                <div class="row">
                    {{-- Type --}}
                    <div class="col-md-6 mb-1">
                        <label>@lang('certificates.fields.type') <span class="text-danger">*</span></label>
                        <select name="type" class="form-control select2 @error('type') is-invalid @enderror">
                            <option value="">@lang('certificates.choose_type')</option>
                            @foreach(\App\Models\Certificate::TYPES as $t)
                                <option value="{{ $t }}"
                                    @selected(old('type', $isEdit ? $certificate->type : '') === $t)>
                                    {{ __('certificates.types.' . $t) }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6 mb-1">
                        <label>@lang('certificates.fields.status') <span class="text-danger">*</span></label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror">
                            @foreach(\App\Models\Certificate::STATUSES as $s)
                                <option value="{{ $s }}"
                                    @selected(old('status', $isEdit ? $certificate->status : 'draft') === $s)>
                                    {{ __('certificates.status.' . $s) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Title --}}
                    <div class="col-md-12 mb-1">
                        <label>@lang('certificates.fields.title') <span class="text-danger">*</span></label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $isEdit ? $certificate->title : '') }}"
                               maxlength="255">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Recipient --}}
                    <div class="col-md-6 mb-1">
                        <label>@lang('certificates.fields.recipient') <span class="text-danger">*</span></label>
                        <select name="recipient_user_id"
                                class="form-control select2 @error('recipient_user_id') is-invalid @enderror">
                            <option value="">@lang('certificates.choose_recipient')</option>
                            @foreach($recipients as $u)
                                <option value="{{ $u->id }}"
                                    @selected((string) old('recipient_user_id', $isEdit ? $certificate->recipient_user_id : '') === (string) $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('recipient_user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Issue Date --}}
                    <div class="col-md-6 mb-1">
                        <label>@lang('certificates.fields.issue_date') <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date"
                               class="form-control @error('issue_date') is-invalid @enderror"
                               value="{{ old('issue_date', $isEdit && $certificate->issue_date ? $certificate->issue_date->format('Y-m-d') : '') }}">
                        @error('issue_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- File --}}
                    <div class="col-md-6 mb-1">
                        <label>@lang('certificates.fields.file')</label>
                        <input type="file" name="file"
                               class="form-control @error('file') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($isEdit && $certificate->file_path)
                            <small class="text-muted">
                                @lang('certificates.actions.download'):
                                <a href="{{ asset('storage/' . $certificate->file_path) }}" target="_blank">
                                    {{ basename($certificate->file_path) }}
                                </a>
                            </small>
                        @endif
                    </div>

                    {{-- Note --}}
                    <div class="col-md-12 mb-1">
                        <label>@lang('certificates.fields.note')</label>
                        <textarea name="note"
                                  class="form-control @error('note') is-invalid @enderror"
                                  rows="3"
                                  maxlength="2000">{{ old('note', $isEdit ? $certificate->note : '') }}</textarea>
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary">
                        <x-svg-icon name="save" /> @lang('certificates.actions.save')
                    </button>
                    <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary ml-1">
                        <x-svg-icon name="arrow-left" /> @lang('certificates.actions.cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
