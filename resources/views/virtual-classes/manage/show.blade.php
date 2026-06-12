@extends('layouts.app')

@section('title', $vc->title)
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $vc->title }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">@lang('virtual_classes.breadcrumb_home')</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('manage.virtual-classes.index') }}">@lang('virtual_classes.title')</a>
                </li>
                <li class="breadcrumb-item active">{{ Str::limit($vc->title, 40) }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-md-block d-none">
        @if($vc->status !== 'cancelled' && $vc->status !== 'ended')
        <a href="{{ route('manage.virtual-classes.edit', $vc->id) }}" class="btn btn-outline-primary">
            <i class="la la-edit"></i> @lang('virtual_classes.btn_edit')
        </a>
        @endif
    </div>
</div>

@include('components.alerts')

<div class="card">
    <div class="card-content collapse show">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-bordered">
                        <tr>
                            <th class="w-40">@lang('virtual_classes.field_status')</th>
                            <td>
                                <span class="badge badge-{{ $vc->statusColor() }}">{{ $vc->statusLabel() }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('virtual_classes.field_teacher')</th>
                            <td>{{ optional($vc->teacher)->name }}</td>
                        </tr>
                        <tr>
                            <th>@lang('virtual_classes.field_scheduled_at')</th>
                            <td>{{ $vc->scheduled_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        <tr>
                            <th>@lang('virtual_classes.field_duration')</th>
                            <td>{{ $vc->duration_minutes }} @lang('virtual_classes.minutes')</td>
                        </tr>
                        <tr>
                            <th>@lang('virtual_classes.field_created_by')</th>
                            <td>{{ optional($vc->creator)->name }}</td>
                        </tr>
                        <tr>
                            <th>@lang('virtual_classes.field_created_at')</th>
                            <td>{{ $vc->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @if($vc->audience)
                        <tr>
                            <th>@lang('virtual_classes.field_audience')</th>
                            <td>
                                @foreach($vc->audience as $aud)
                                    <span class="badge badge-light">@lang('virtual_classes.audience_' . $aud)</span>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div class="col-md-6">
                    {{-- Zoom info --}}
                    @if($vc->zoom_meeting_id)
                    <div class="card border-success mb-2">
                        <div class="card-header bg-success text-white py-1 px-2">
                            <i class="la la-video"></i> @lang('virtual_classes.zoom_info')
                        </div>
                        <div class="card-body py-2">
                            <p class="mb-1">
                                <strong>@lang('virtual_classes.zoom_meeting_id'):</strong>
                                {{ $vc->zoom_meeting_id }}
                            </p>
                            @if($vc->passcode)
                            <p class="mb-1">
                                <strong>@lang('virtual_classes.zoom_passcode'):</strong>
                                {{ $vc->passcode }}
                            </p>
                            @endif
                            @if($vc->join_url)
                            <a href="{{ $vc->join_url }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-success mb-1">
                                <i class="la la-sign-in-alt"></i> @lang('virtual_classes.zoom_join')
                            </a>
                            @endif
                            @if($vc->start_url)
                            <a href="{{ $vc->start_url }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-primary mb-1">
                                <i class="la la-play"></i> @lang('virtual_classes.zoom_start')
                            </a>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="la la-exclamation-triangle"></i>
                        @lang('virtual_classes.zoom_not_linked')
                    </div>
                    @endif

                    {{-- Description --}}
                    @if($vc->description)
                    <div class="mt-2">
                        <strong>@lang('virtual_classes.field_description'):</strong>
                        <p class="mt-1">{{ $vc->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
