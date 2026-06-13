@extends('layouts.app')

@section('title', __('surveys.admin_title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('surveys.admin_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('surveys.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('surveys.breadcrumb_index')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }}">
        <a href="{{ route('admin.surveys.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('surveys.actions.create')
        </a>
    </div>
</div>

<div class="card">
    <div class="card-content">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('surveys.fields.title')</th>
                        <th>@lang('surveys.fields.audience')</th>
                        <th>@lang('surveys.fields.status')</th>
                        <th>@lang('surveys.fields.responses')</th>
                        <th>@lang('surveys.fields.starts_at')</th>
                        <th>@lang('surveys.fields.ends_at')</th>
                        <th>@lang('surveys.fields.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($surveys as $survey)
                        <tr>
                            <td>{{ $survey->id }}</td>
                            <td>{{ $survey->title }}</td>
                            <td>{{ __('surveys.audiences.' . $survey->audience) }}</td>
                            <td>
                                @if($survey->status === 'published')
                                    <span class="badge badge-success">@lang('surveys.statuses.published')</span>
                                @elseif($survey->status === 'closed')
                                    <span class="badge badge-secondary">@lang('surveys.statuses.closed')</span>
                                @else
                                    <span class="badge badge-warning">@lang('surveys.statuses.draft')</span>
                                @endif
                            </td>
                            <td>{{ $survey->responses_count }}</td>
                            <td>{{ $survey->starts_at ? $survey->starts_at->format('Y-m-d') : '—' }}</td>
                            <td>{{ $survey->ends_at ? $survey->ends_at->format('Y-m-d') : '—' }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    {{-- Publish --}}
                                    @if($survey->status === 'draft')
                                        <form method="POST"
                                              action="{{ route('admin.surveys.publish', $survey->id) }}"
                                              id="publish-form-{{ $survey->id }}"
                                              style="display:inline">
                                            @csrf
                                            <button type="button" class="btn btn-sm btn-success"
                                                    onclick="vcConfirm({title: @json(__('surveys.confirm.publish'))}).then(function(r){ if(r.isConfirmed) document.getElementById('publish-form-{{ $survey->id }}').submit(); })">
                                                <i class="la la-check-circle"></i> @lang('surveys.actions.publish')
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Close --}}
                                    @if($survey->status === 'published')
                                        <form method="POST"
                                              action="{{ route('admin.surveys.close', $survey->id) }}"
                                              id="close-form-{{ $survey->id }}"
                                              style="display:inline">
                                            @csrf
                                            <button type="button" class="btn btn-sm btn-warning"
                                                    onclick="vcConfirm({title: @json(__('surveys.confirm.close'))}).then(function(r){ if(r.isConfirmed) document.getElementById('close-form-{{ $survey->id }}').submit(); })">
                                                <i class="la la-lock"></i> @lang('surveys.actions.close')
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Results --}}
                                    <a href="{{ route('admin.surveys.results', $survey->id) }}"
                                       class="btn btn-sm btn-info">
                                        <i class="la la-chart-bar"></i> @lang('surveys.actions.results')
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.surveys.edit', $survey->id) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="la la-edit"></i> @lang('surveys.actions.edit')
                                    </a>

                                    {{-- Delete --}}
                                    <form method="POST"
                                          action="{{ route('admin.surveys.destroy', $survey->id) }}"
                                          id="delete-form-{{ $survey->id }}"
                                          style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="vcConfirm({title: @json(__('surveys.confirm.delete')), icon: 'error'}).then(function(r){ if(r.isConfirmed) document.getElementById('delete-form-{{ $survey->id }}').submit(); })">
                                            <i class="la la-trash"></i> @lang('surveys.actions.delete')
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">
                                @lang('surveys.empty')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($surveys->hasPages())
            <div class="p-2">
                {{ $surveys->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
