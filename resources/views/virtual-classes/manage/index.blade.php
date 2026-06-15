@extends('layouts.app')

@section('title', __('virtual_classes.title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $u     = auth()->user();
    $tabs  = ['today' => 'tab_today', 'recorded' => 'tab_recorded', 'old' => 'tab_old', 'all' => 'tab_all'];
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('virtual_classes.title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">@lang('virtual_classes.breadcrumb_home')</a>
                </li>
                <li class="breadcrumb-item active">@lang('virtual_classes.title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-md-block d-none">
        @if($u->canDo('virtual_classes.create'))
        <a href="{{ route('manage.virtual-classes.create') }}" class="btn btn-primary">
            <x-svg-icon name="plus-lg" /> @lang('virtual_classes.btn_create')
        </a>
        @endif
    </div>
</div>

{{-- Tabs (card #234) --}}
<ul class="nav nav-tabs mb-2" role="tablist">
    @foreach($tabs as $key => $labelKey)
    <li class="nav-item">
        <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
           href="{{ route('manage.virtual-classes.index', ['tab' => $key]) }}">
            @lang('virtual_classes.' . $labelKey)
        </a>
    </li>
    @endforeach
</ul>

<div class="ds-card card">
    <div class="ds-card-header card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="ds-card-title mb-0"><x-svg-icon name="camera-video" :size="16" /> @lang('virtual_classes.title')</h5>
    </div>
    @if($classes->isEmpty())
        <div class="ds-empty">
            <div class="ds-empty-icon"><x-svg-icon name="camera-video-off" :size="36" /></div>
            <div class="ds-empty-title">@lang('virtual_classes.empty')</div>
            <div class="ds-empty-desc">@lang('virtual_classes.empty')</div>
            @if($u->canDo('virtual_classes.create'))
            <a href="{{ route('manage.virtual-classes.create') }}" class="btn btn-primary btn-sm mt-2">
                <x-svg-icon name="plus-lg" :size="15" /> @lang('virtual_classes.btn_create')
            </a>
            @endif
        </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>@lang('virtual_classes.field_teacher')</th>
                    <th>@lang('virtual_classes.field_subject')</th>
                    <th>@lang('virtual_classes.field_class')</th>
                    <th>@lang('virtual_classes.field_title')</th>
                    <th>@lang('virtual_classes.field_scheduled_at')</th>
                    <th>@lang('virtual_classes.field_duration')</th>
                    <th>@lang('virtual_classes.field_platform')</th>
                    <th>@lang('virtual_classes.field_started')</th>
                    <th>@lang('virtual_classes.field_actions')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($classes as $vc)
                <tr>
                    <td>{{ $vc->id }}</td>
                    <td>{{ optional($vc->teacher)->name }}</td>
                    <td>{{ optional($vc->subject)->name ?? '—' }}</td>
                    <td>{{ optional($vc->classRoom)->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route('manage.virtual-classes.show', $vc->id) }}" class="fw-bold text-navy">{{ $vc->title }}</a>
                        <span class="badge badge-{{ $vc->statusColor() }} ms-1 me-1">{{ $vc->statusLabel() }}</span>
                    </td>
                    <td class="text-nowrap" dir="ltr">{{ $vc->scheduled_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $vc->duration_minutes }} @lang('virtual_classes.minutes')</td>
                    <td>{{ $vc->platformLabel() }}</td>
                    <td>
                        @if($vc->started_at)
                            <span class="ds-badge-success badge" title="{{ $vc->started_at->format('Y-m-d H:i') }}">
                                <x-svg-icon name="check-lg" :size="13" /> @lang('virtual_classes.started_yes')
                            </span>
                        @else
                            <span class="badge badge-light text-muted">@lang('virtual_classes.started_no')</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        {{-- View --}}
                        <a href="{{ route('manage.virtual-classes.show', $vc->id) }}"
                           class="ds-action-btn" title="@lang('virtual_classes.btn_show')" aria-label="@lang('virtual_classes.btn_show')">
                            <x-svg-icon name="eye" :size="15" />
                        </a>

                        {{-- Start (host) — only inside the join window --}}
                        @if($u->canDo('virtual_classes.start') && ($vc->isJoinable() || $vc->status === 'live') && $vc->status !== 'cancelled')
                        <form method="POST" action="{{ route('manage.virtual-classes.start', $vc->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="ds-action-btn text-success" title="@lang('virtual_classes.btn_start')" aria-label="@lang('virtual_classes.btn_start')">
                                <x-svg-icon name="play-fill" :size="15" />
                            </button>
                        </form>
                        @endif

                        {{-- Attendance --}}
                        @if($u->canDo('virtual_classes.view_attendance'))
                        <a href="{{ route('manage.virtual-classes.attendance', $vc->id) }}"
                           class="ds-action-btn" title="@lang('virtual_classes.btn_attendance')" aria-label="@lang('virtual_classes.btn_attendance')">
                            <x-svg-icon name="people" :size="15" />
                        </a>
                        @endif

                        {{-- Edit --}}
                        @if($u->canDo('virtual_classes.edit') && $vc->status !== 'cancelled' && $vc->status !== 'ended')
                        <a href="{{ route('manage.virtual-classes.edit', $vc->id) }}"
                           class="ds-action-btn" title="@lang('virtual_classes.btn_edit')" aria-label="@lang('virtual_classes.btn_edit')">
                            <x-svg-icon name="pencil" :size="15" />
                        </a>

                        {{-- Cancel --}}
                        <form method="POST" action="{{ route('manage.virtual-classes.cancel', $vc->id) }}"
                              class="d-inline" id="cancelVcForm{{ $vc->id }}">
                            @csrf
                            <button type="button" class="ds-action-btn text-warning"
                                    title="@lang('virtual_classes.btn_cancel')"
                                    aria-label="@lang('virtual_classes.btn_cancel')"
                                    onclick="vcConfirm({ title: '{{ __('virtual_classes.confirm_cancel') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('cancelVcForm{{ $vc->id }}').submit(); } })">
                                <x-svg-icon name="slash-circle" :size="15" />
                            </button>
                        </form>
                        @endif

                        {{-- Delete --}}
                        @if($u->canDo('virtual_classes.delete'))
                        <form method="POST" action="{{ route('manage.virtual-classes.destroy', $vc->id) }}"
                              class="d-inline" id="deleteVcForm{{ $vc->id }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="ds-action-btn text-danger"
                                    title="@lang('virtual_classes.btn_delete')"
                                    aria-label="@lang('virtual_classes.btn_delete')"
                                    onclick="vcConfirm({ title: '{{ __('virtual_classes.confirm_delete') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteVcForm{{ $vc->id }}').submit(); } })">
                                <x-svg-icon name="trash" :size="15" />
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($classes->hasPages())
        <div class="ds-card-footer card-footer">{{ $classes->links() }}</div>
    @endif
    @endif
</div>
@endsection
