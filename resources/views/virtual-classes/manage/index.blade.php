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

<div class="card">
    <div class="card-content">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
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
                    @forelse($classes as $vc)
                    <tr>
                        <td>{{ $vc->id }}</td>
                        <td>{{ optional($vc->teacher)->name }}</td>
                        <td>{{ optional($vc->subject)->name ?? '—' }}</td>
                        <td>{{ optional($vc->classRoom)->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('manage.virtual-classes.show', $vc->id) }}">{{ $vc->title }}</a>
                            <span class="badge badge-{{ $vc->statusColor() }} ml-1">{{ $vc->statusLabel() }}</span>
                        </td>
                        <td class="text-nowrap">{{ $vc->scheduled_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $vc->duration_minutes }} @lang('virtual_classes.minutes')</td>
                        <td>{{ $vc->platformLabel() }}</td>
                        <td>
                            @if($vc->started_at)
                                <span class="badge badge-success" title="{{ $vc->started_at->format('Y-m-d H:i') }}">
                                    <x-svg-icon name="check-lg" size="14" /> @lang('virtual_classes.started_yes')
                                </span>
                            @else
                                <span class="badge badge-light">@lang('virtual_classes.started_no')</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            {{-- View --}}
                            <a href="{{ route('manage.virtual-classes.show', $vc->id) }}"
                               class="btn btn-sm btn-outline-info" title="@lang('virtual_classes.btn_show')">
                                <x-svg-icon name="eye" size="14" />
                            </a>

                            {{-- Start (host) — only inside the join window --}}
                            @if($u->canDo('virtual_classes.start') && ($vc->isJoinable() || $vc->status === 'live') && $vc->status !== 'cancelled')
                            <form method="POST" action="{{ route('manage.virtual-classes.start', $vc->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="@lang('virtual_classes.btn_start')">
                                    <x-svg-icon name="play-fill" size="14" />
                                </button>
                            </form>
                            @endif

                            {{-- Attendance --}}
                            @if($u->canDo('virtual_classes.view_attendance'))
                            <a href="{{ route('manage.virtual-classes.attendance', $vc->id) }}"
                               class="btn btn-sm btn-outline-secondary" title="@lang('virtual_classes.btn_attendance')">
                                <x-svg-icon name="people" size="14" />
                            </a>
                            @endif

                            {{-- Edit / Cancel --}}
                            @if($u->canDo('virtual_classes.edit') && $vc->status !== 'cancelled' && $vc->status !== 'ended')
                            <a href="{{ route('manage.virtual-classes.edit', $vc->id) }}"
                               class="btn btn-sm btn-outline-primary" title="@lang('virtual_classes.btn_edit')">
                                <x-svg-icon name="pencil" size="14" />
                            </a>
                            <form method="POST" action="{{ route('manage.virtual-classes.cancel', $vc->id) }}"
                                  class="d-inline" id="cancelVcForm{{ $vc->id }}">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-warning"
                                        title="@lang('virtual_classes.btn_cancel')"
                                        onclick="vcConfirm({ title: '{{ __('virtual_classes.confirm_cancel') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('cancelVcForm{{ $vc->id }}').submit(); } })">
                                    <x-svg-icon name="slash-circle" size="14" />
                                </button>
                            </form>
                            @endif

                            {{-- Delete --}}
                            @if($u->canDo('virtual_classes.delete'))
                            <form method="POST" action="{{ route('manage.virtual-classes.destroy', $vc->id) }}"
                                  class="d-inline" id="deleteVcForm{{ $vc->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        title="@lang('virtual_classes.btn_delete')"
                                        onclick="vcConfirm({ title: '{{ __('virtual_classes.confirm_delete') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteVcForm{{ $vc->id }}').submit(); } })">
                                    <x-svg-icon name="trash" size="14" />
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <x-svg-icon name="camera-video-off" size="28" class="d-block mx-auto mb-2 text-muted" />
                            @lang('virtual_classes.empty')
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($classes->hasPages())
            <div class="p-2">{{ $classes->links() }}</div>
        @endif
    </div>
</div>
@endsection
