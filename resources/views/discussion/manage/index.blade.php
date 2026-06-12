@extends('layouts.app')

@section('title', __('discussion.page_title_manage'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('discussion.page_title_manage')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('discussion.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('discussion.breadcrumb_manage')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
        <a href="{{ route('manage.discussion-rooms.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('discussion.btn_create_room')
        </a>
    </div>
</div>

@include('components.alerts')

{{-- Filter --}}
<div class="card mb-2">
    <div class="card-content">
        <div class="card-body py-1">
            <form method="GET" action="{{ route('manage.discussion-rooms.index') }}" class="form-row align-items-end">
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('discussion.filter_status')</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">— @lang('discussion.filter_all') —</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>@lang('discussion.status_active')</option>
                        <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>@lang('discussion.status_closed')</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="la la-search"></i> @lang('discussion.filter_apply')
                    </button>
                    <a href="{{ route('manage.discussion-rooms.index') }}" class="btn btn-sm btn-secondary ml-1">
                        <i class="la la-redo"></i> @lang('discussion.filter_reset')
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
                        <th>@lang('discussion.field_title')</th>
                        <th>@lang('discussion.field_topics_count')</th>
                        <th>@lang('discussion.field_status')</th>
                        <th>@lang('discussion.field_created_by')</th>
                        <th>@lang('discussion.field_created_at')</th>
                        <th>@lang('discussion.field_actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>{{ $room->id }}</td>
                            <td>
                                <a href="{{ route('discussion.room', $room->id) }}">{{ $room->title }}</a>
                                @if($room->description)
                                    <br><small class="text-muted">{{ Str::limit($room->description, 60) }}</small>
                                @endif
                            </td>
                            <td>{{ $room->topics_count }}</td>
                            <td>
                                @if($room->status === 'active')
                                    <span class="badge badge-success">@lang('discussion.status_active')</span>
                                @else
                                    <span class="badge badge-secondary">@lang('discussion.status_closed')</span>
                                @endif
                            </td>
                            <td>{{ optional($room->creator)->name }}</td>
                            <td>{{ $room->created_at->format('Y-m-d') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('manage.discussion-rooms.edit', $room->id) }}"
                                   class="btn btn-sm btn-info">
                                    <i class="la la-edit"></i>
                                </a>

                                @if($room->status === 'active')
                                    <form method="POST"
                                          action="{{ route('manage.discussion-rooms.close', $room->id) }}"
                                          class="d-inline"
                                          id="closeRoomForm{{ $room->id }}">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-warning"
                                                onclick="vcConfirm({ title: '{{ __('discussion.confirm_close_room') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('closeRoomForm{{ $room->id }}').submit(); } })">
                                            <i class="la la-lock"></i>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST"
                                      action="{{ route('manage.discussion-rooms.destroy', $room->id) }}"
                                      class="d-inline"
                                      id="deleteRoomForm{{ $room->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger"
                                            onclick="vcConfirm({ title: '{{ __('discussion.confirm_delete_room') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteRoomForm{{ $room->id }}').submit(); } })">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                @lang('discussion.empty_rooms')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rooms->hasPages())
            <div class="p-2">{{ $rooms->links() }}</div>
        @endif
    </div>
</div>
@endsection
