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
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        @if(auth()->user()->canDo('virtual_classes.view_attendance'))
        <a href="{{ route('manage.virtual-classes.attendance', $vc->id) }}" class="btn btn-outline-secondary">
            <x-svg-icon name="people" size="16" /> @lang('virtual_classes.btn_attendance')
        </a>
        @endif
        @if(auth()->user()->canDo('virtual_classes.edit') && $vc->status !== 'cancelled' && $vc->status !== 'ended')
        <a href="{{ route('manage.virtual-classes.edit', $vc->id) }}" class="btn btn-outline-primary">
            <x-svg-icon name="pencil" size="16" /> @lang('virtual_classes.btn_edit')
        </a>
        @endif
    </div>
</div>


<div class="ds-card card">
    <div class="ds-card-header card-header d-flex align-items-center gap-2">
        <h5 class="ds-card-title mb-0"><x-svg-icon name="camera-video" :size="16" /> {{ Str::limit($vc->title, 60) }}</h5>
        <span class="badge badge-{{ $vc->statusColor() }} ms-auto">{{ $vc->statusLabel() }}</span>
    </div>
    <div class="ds-card-body card-body">
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
                        <th>@lang('virtual_classes.field_subject')</th>
                        <td>{{ optional($vc->subject)->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>@lang('virtual_classes.field_class')</th>
                        <td>{{ optional($vc->classRoom)->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>@lang('virtual_classes.field_platform')</th>
                        <td>{{ $vc->platformLabel() }}</td>
                    </tr>
                    <tr>
                        <th>@lang('virtual_classes.field_scheduled_at')</th>
                        <td dir="ltr" class="text-start">{{ $vc->scheduled_at->format('Y-m-d H:i') }}</td>
                    </tr>
                    <tr>
                        <th>@lang('virtual_classes.field_started')</th>
                        <td>
                            @if($vc->started_at)
                                <span class="ds-badge-success badge" dir="ltr">{{ $vc->started_at->format('Y-m-d H:i') }}</span>
                            @else
                                <span class="badge badge-light text-muted">@lang('virtual_classes.started_no')</span>
                            @endif
                        </td>
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
                        <td dir="ltr" class="text-start">{{ $vc->created_at->format('Y-m-d H:i') }}</td>
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
                <div class="ds-card card mb-2">
                    <div class="ds-card-header card-header">
                        <h6 class="ds-card-title mb-0 text-gold">
                            <x-svg-icon name="camera-video" :size="15" /> @lang('virtual_classes.zoom_info')
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <p class="mb-1">
                            <strong>@lang('virtual_classes.zoom_meeting_id'):</strong>
                            <span dir="ltr">{{ $vc->zoom_meeting_id }}</span>
                        </p>
                        @if($vc->passcode)
                        <p class="mb-1">
                            <strong>@lang('virtual_classes.zoom_passcode'):</strong>
                            {{ $vc->passcode }}
                        </p>
                        @endif
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @if($vc->join_url)
                            <a href="{{ $vc->join_url }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-success">
                                <x-svg-icon name="box-arrow-in-right" :size="14" /> @lang('virtual_classes.zoom_join')
                            </a>
                            @endif
                            @if($vc->start_url)
                            <a href="{{ $vc->start_url }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-primary">
                                <x-svg-icon name="play-circle" :size="14" /> @lang('virtual_classes.zoom_start')
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <x-svg-icon name="exclamation-circle" :size="16" />
                    @lang('virtual_classes.zoom_not_linked')
                </div>
                @endif

                {{-- Description --}}
                @if($vc->description)
                <div class="mt-2">
                    <strong>@lang('virtual_classes.field_description'):</strong>
                    <p class="mt-1 text-muted">{{ $vc->description }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
