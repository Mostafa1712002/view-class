@extends('layouts.app')

@section('title', __('surveys.my_title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('surveys.my_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('surveys.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('surveys.breadcrumb_my')</li>
            </ol>
        </div>
    </div>
</div>

{{-- ── Pending surveys ──────────────────────────────────────────────────────── --}}
<h5 class="mb-1">@lang('surveys.pending_surveys')</h5>

@if($pending->isEmpty())
    <div class="alert alert-light mb-3">@lang('surveys.empty_pending')</div>
@else
    <div class="row mb-3">
        @foreach($pending as $survey)
            <div class="col-md-4 col-sm-6 mb-2">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">{{ $survey->title }}</h6>
                        @if($survey->description)
                            <p class="card-text text-muted small flex-grow-1">
                                {{ Str::limit($survey->description, 100) }}
                            </p>
                        @endif
                        <div class="mt-auto pt-1">
                            @if($survey->ends_at)
                                <p class="text-muted small mb-1">
                                    @lang('surveys.fields.ends_at'): {{ $survey->ends_at->format('Y-m-d') }}
                                </p>
                            @endif
                            <a href="{{ route('my.surveys.show', $survey->id) }}" class="btn btn-primary btn-sm">
                                @lang('surveys.actions.take')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── Answered surveys ─────────────────────────────────────────────────────── --}}
<h5 class="mb-1">@lang('surveys.answered_surveys')</h5>

@if($answered->isEmpty())
    <div class="alert alert-light">@lang('surveys.empty_answered')</div>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>@lang('surveys.fields.title')</th>
                    <th>@lang('surveys.fields.audience')</th>
                    <th>@lang('surveys.fields.ends_at')</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($answered as $survey)
                    <tr>
                        <td>{{ $survey->title }}</td>
                        <td>{{ __('surveys.audiences.' . $survey->audience) }}</td>
                        <td>{{ $survey->ends_at ? $survey->ends_at->format('Y-m-d') : '—' }}</td>
                        <td><span class="badge badge-success">@lang('surveys.answered_badge')</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
