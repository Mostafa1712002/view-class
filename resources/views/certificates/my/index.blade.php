@extends('layouts.app')

@section('title', __('certificates.my_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('certificates.my_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('certificates.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('certificates.my_title')</li>
            </ol>
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
                        <th>@lang('certificates.fields.issued_by')</th>
                        <th>@lang('certificates.fields.issue_date')</th>
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
                            <td>{{ optional($cert->issuer)->name ?? '—' }}</td>
                            <td>{{ $cert->issue_date ? $cert->issue_date->format('Y-m-d') : '—' }}</td>
                            <td>
                                @if($cert->status === 'published')
                                    <span class="badge badge-success">@lang('certificates.status.published')</span>
                                @else
                                    <span class="badge badge-secondary">@lang('certificates.status.draft')</span>
                                @endif
                            </td>
                            <td>
                                @if($cert->file_path)
                                    <a href="{{ asset('storage/' . $cert->file_path) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-secondary">
                                        <x-svg-icon name="download" /> @lang('certificates.actions.download')
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">
                                @lang('certificates.empty_my')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
