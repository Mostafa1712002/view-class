@extends('layouts.app')

@section('title', __('certificates.admin_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('certificates.admin_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('certificates.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('certificates.breadcrumb_index')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-6 col-12 d-flex flex-wrap justify-content-{{ $isRtl ? 'start' : 'end' }} gap-1">
        <a href="{{ route('admin.certificate-templates.index') }}" class="btn btn-secondary mr-1 mb-1">
            <x-svg-icon name="palette" /> @lang('certificates.templates_btn')
        </a>
        <a href="{{ route('admin.certificates.issue.form') }}" class="btn btn-primary mr-1 mb-1">
            <x-svg-icon name="award" /> @lang('certificates.issue_btn')
        </a>
        <a href="{{ route('admin.certificates.index') }}" class="btn btn-light mb-1">
            <x-svg-icon name="arrow-clockwise" /> @lang('certificates.refresh')
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Filter bar --}}
<div class="card mb-2">
    <div class="card-content">
        <div class="card-body py-1">
            <form method="GET" action="{{ route('admin.certificates.index') }}" class="form-row align-items-end">
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('certificates.filter_type')</label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="">— @lang('certificates.filter_all') —</option>
                        @foreach(\App\Models\Certificate::TYPES as $t)
                            <option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>
                                {{ __('certificates.types.' . $t) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('certificates.filter_q')</label>
                    <input type="text" name="q" class="form-control form-control-sm"
                           value="{{ $filters['q'] ?? '' }}"
                           placeholder="@lang('certificates.filter_q')">
                </div>
                <div class="col-md-4 col-sm-12 mb-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <x-svg-icon name="search" /> @lang('certificates.filter_apply')
                    </button>
                    <a href="{{ route('admin.certificates.index') }}" class="btn btn-sm btn-secondary ml-1">
                        <x-svg-icon name="arrow-clockwise" /> @lang('certificates.filter_reset')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-content">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('certificates.fields.title')</th>
                        <th>@lang('certificates.fields.type')</th>
                        <th>@lang('certificates.fields.recipient')</th>
                        <th>@lang('certificates.fields.issue_date')</th>
                        <th>@lang('certificates.fields.progress')</th>
                        <th>@lang('certificates.fields.status')</th>
                        <th>@lang('certificates.fields.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificates as $cert)
                        <tr>
                            <td>{{ $cert->id }}</td>
                            <td>{{ $cert->title }}</td>
                            <td>{{ __('certificates.types.' . $cert->type) }}</td>
                            <td>{{ optional($cert->recipient)->name ?? '—' }}</td>
                            <td>{{ $cert->issue_date ? $cert->issue_date->format('Y-m-d') : '—' }}</td>
                            <td style="min-width:90px">
                                <div class="progress" style="height:14px">
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width: {{ (int) $cert->progress }}%"
                                         aria-valuenow="{{ (int) $cert->progress }}" aria-valuemin="0" aria-valuemax="100">
                                        {{ (int) $cert->progress }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($cert->status === 'published')
                                    <span class="badge badge-success">@lang('certificates.status.published')</span>
                                @else
                                    <span class="badge badge-secondary">@lang('certificates.status.draft')</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($cert->status !== 'published')
                                        {{-- Publish form --}}
                                        <form method="POST"
                                              action="{{ route('admin.certificates.publish', $cert->id) }}"
                                              id="publish-form-{{ $cert->id }}"
                                              style="display:inline">
                                            @csrf
                                            <button type="button" class="btn btn-sm btn-success"
                                                    onclick="vcConfirm({title: @json(__('certificates.confirm_publish'))}).then(function(r){ if(r.isConfirmed) document.getElementById('publish-form-{{ $cert->id }}').submit(); })">
                                                <x-svg-icon name="check-circle" /> @lang('certificates.actions.publish')
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.certificates.preview', $cert->id) }}"
                                       class="btn btn-sm btn-primary">
                                        <x-svg-icon name="eye" /> @lang('certificates.preview_page.view')
                                    </a>

                                    <a href="{{ route('admin.certificates.send', $cert->id) }}"
                                       class="btn btn-sm btn-success">
                                        <x-svg-icon name="send" /> @lang('certificates.preview_page.send')
                                    </a>

                                    <a href="{{ route('admin.certificates.edit', $cert->id) }}"
                                       class="btn btn-sm btn-info">
                                        <x-svg-icon name="pencil-square" /> @lang('certificates.actions.edit')
                                    </a>

                                    @if($cert->file_path)
                                        <a href="{{ asset('storage/' . $cert->file_path) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-secondary">
                                            <x-svg-icon name="download" /> @lang('certificates.actions.download')
                                        </a>
                                    @endif

                                    {{-- Delete form --}}
                                    <form method="POST"
                                          action="{{ route('admin.certificates.destroy', $cert->id) }}"
                                          id="delete-form-{{ $cert->id }}"
                                          style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="vcConfirm({title: @json(__('certificates.confirm_delete')), icon: 'error'}).then(function(r){ if(r.isConfirmed) document.getElementById('delete-form-{{ $cert->id }}').submit(); })">
                                            <x-svg-icon name="trash" /> @lang('certificates.actions.delete')
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">
                                @lang('certificates.empty')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($certificates->hasPages())
            <div class="p-2">
                {{ $certificates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
