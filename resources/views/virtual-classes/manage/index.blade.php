@extends('layouts.app')

@section('title', __('virtual_classes.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

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
        <a href="{{ route('manage.virtual-classes.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('virtual_classes.btn_create')
        </a>
    </div>
</div>

@include('components.alerts')

<div class="card">
    <div class="card-content">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('virtual_classes.field_title')</th>
                        <th>@lang('virtual_classes.field_teacher')</th>
                        <th>@lang('virtual_classes.field_scheduled_at')</th>
                        <th>@lang('virtual_classes.field_duration')</th>
                        <th>@lang('virtual_classes.field_status')</th>
                        <th>@lang('virtual_classes.field_zoom')</th>
                        <th>@lang('virtual_classes.field_actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $vc)
                    <tr>
                        <td>{{ $vc->id }}</td>
                        <td>
                            <a href="{{ route('manage.virtual-classes.show', $vc->id) }}">
                                {{ $vc->title }}
                            </a>
                            @if($vc->description)
                                <br><small class="text-muted">{{ Str::limit($vc->description, 60) }}</small>
                            @endif
                        </td>
                        <td>{{ optional($vc->teacher)->name }}</td>
                        <td>{{ $vc->scheduled_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $vc->duration_minutes }} @lang('virtual_classes.minutes')</td>
                        <td>
                            <span class="badge badge-{{ $vc->statusColor() }}">{{ $vc->statusLabel() }}</span>
                        </td>
                        <td>
                            @if($vc->zoom_meeting_id)
                                <span class="badge badge-success"><i class="la la-video"></i> @lang('virtual_classes.zoom_linked')</span>
                            @else
                                <span class="badge badge-light">@lang('virtual_classes.zoom_none')</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('manage.virtual-classes.show', $vc->id) }}"
                               class="btn btn-sm btn-outline-info" title="@lang('virtual_classes.btn_show')">
                                <i class="la la-eye"></i>
                            </a>
                            @if($vc->status !== 'cancelled' && $vc->status !== 'ended')
                            <a href="{{ route('manage.virtual-classes.edit', $vc->id) }}"
                               class="btn btn-sm btn-outline-primary" title="@lang('virtual_classes.btn_edit')">
                                <i class="la la-edit"></i>
                            </a>
                            <form method="POST"
                                  action="{{ route('manage.virtual-classes.cancel', $vc->id) }}"
                                  class="d-inline"
                                  id="cancelVcForm{{ $vc->id }}">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-warning"
                                        title="@lang('virtual_classes.btn_cancel')"
                                        onclick="vcConfirm({ title: '{{ __('virtual_classes.confirm_cancel') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('cancelVcForm{{ $vc->id }}').submit(); } })">
                                    <i class="la la-ban"></i>
                                </button>
                            </form>
                            @endif
                            <form method="POST"
                                  action="{{ route('manage.virtual-classes.destroy', $vc->id) }}"
                                  class="d-inline"
                                  id="deleteVcForm{{ $vc->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        title="@lang('virtual_classes.btn_delete')"
                                        onclick="vcConfirm({ title: '{{ __('virtual_classes.confirm_delete') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteVcForm{{ $vc->id }}').submit(); } })">
                                    <i class="la la-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">
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
