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
            <x-svg-icon name="plus-lg" :size="16" /> @lang('discussion.btn_create_room')
        </a>
    </div>
</div>


{{-- Filter --}}
<div class="ds-card mb-2">
    <div class="ds-card-body py-1">
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
                    <x-svg-icon name="search" :size="14" /> @lang('discussion.filter_apply')
                </button>
                <a href="{{ route('manage.discussion-rooms.index') }}" class="btn btn-sm btn-outline-secondary ml-1">
                    <x-svg-icon name="arrow-counterclockwise" :size="14" /> @lang('discussion.filter_reset')
                </a>
            </div>
        </form>
    </div>
</div>

<div class="ds-card">
    <div class="ds-card-header">
        <span class="ds-card-title"><x-svg-icon name="chat-dots" :size="16" /> @lang('discussion.breadcrumb_manage')</span>
    </div>
    <div class="ds-card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('discussion.field_title')</th>
                        <th>@lang('discussion.field_topics_count')</th>
                        <th>@lang('discussion.field_comments_count')</th>
                        <th>@lang('discussion.field_last_activity')</th>
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
                            <td>{{ $room->comments_count }}</td>
                            <td><small class="text-muted">{{ $room->last_activity_at ? $room->last_activity_at->diffForHumans() : '—' }}</small></td>
                            <td>
                                @if($room->status === 'active')
                                    <span class="ds-badge-success">@lang('discussion.status_active')</span>
                                @else
                                    <span class="ds-badge-warning">@lang('discussion.status_closed')</span>
                                @endif
                                @unless($room->allow_comments)
                                    <span class="badge badge-light border" title="@lang('discussion.field_allow_comments')"><x-svg-icon name="slash-circle" :size="12" /></span>
                                @endunless
                            </td>
                            <td>{{ optional($room->creator)->name }}</td>
                            <td>{{ $room->created_at->format('Y-m-d') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('discussion.room', $room->id) }}"
                                   class="ds-action-btn" title="@lang('discussion.btn_view')" aria-label="@lang('discussion.btn_view')">
                                    <x-svg-icon name="eye" :size="15" />
                                </a>

                                <a href="{{ route('manage.discussion-rooms.report', $room->id) }}"
                                   class="ds-action-btn" title="@lang('discussion.btn_report')" aria-label="@lang('discussion.btn_report')">
                                    <x-svg-icon name="bar-chart-line" :size="15" />
                                </a>

                                <a href="{{ route('manage.discussion-rooms.edit', $room->id) }}"
                                   class="ds-action-btn" title="@lang('discussion.btn_edit')" aria-label="@lang('discussion.btn_edit')">
                                    <x-svg-icon name="pencil" :size="15" />
                                </a>

                                {{-- Toggle comments (discussion.toggle_comments) --}}
                                <form method="POST"
                                      action="{{ route('manage.discussion-rooms.toggle-comments', $room->id) }}"
                                      class="d-inline">
                                    @csrf
                                    <button type="submit"
                                            class="ds-action-btn"
                                            title="{{ $room->allow_comments ? __('discussion.btn_disable_comments') : __('discussion.btn_enable_comments') }}"
                                            aria-label="{{ $room->allow_comments ? __('discussion.btn_disable_comments') : __('discussion.btn_enable_comments') }}">
                                        <x-svg-icon name="{{ $room->allow_comments ? 'chat' : 'slash-circle' }}" :size="15" />
                                    </button>
                                </form>

                                @if($room->status === 'active')
                                    <form method="POST"
                                          action="{{ route('manage.discussion-rooms.close', $room->id) }}"
                                          class="d-inline"
                                          id="closeRoomForm{{ $room->id }}">
                                        @csrf
                                        <button type="button" class="ds-action-btn"
                                                title="@lang('discussion.btn_close')"
                                                aria-label="@lang('discussion.btn_close')"
                                                onclick="vcConfirm({ title: '{{ __('discussion.confirm_close_room') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('closeRoomForm{{ $room->id }}').submit(); } })">
                                            <x-svg-icon name="lock" :size="15" />
                                        </button>
                                    </form>
                                @else
                                    <form method="POST"
                                          action="{{ route('manage.discussion-rooms.reopen', $room->id) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" class="ds-action-btn"
                                                title="@lang('discussion.btn_reopen')"
                                                aria-label="@lang('discussion.btn_reopen')">
                                            <x-svg-icon name="unlock" :size="15" />
                                        </button>
                                    </form>
                                @endif

                                <form method="POST"
                                      action="{{ route('manage.discussion-rooms.destroy', $room->id) }}"
                                      class="d-inline"
                                      id="deleteRoomForm{{ $room->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ds-action-btn text-danger"
                                            title="@lang('discussion.field_actions')"
                                            aria-label="@lang('discussion.field_actions')"
                                            onclick="vcConfirm({ title: '{{ __('discussion.confirm_delete_room') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteRoomForm{{ $room->id }}').submit(); } })">
                                        <x-svg-icon name="trash" :size="15" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="ds-empty">
                                    <div class="ds-empty-icon"><x-svg-icon name="chat-dots" :size="40" /></div>
                                    <div class="ds-empty-title">@lang('discussion.empty_rooms')</div>
                                    <div class="ds-empty-desc">
                                        <a href="{{ route('manage.discussion-rooms.create') }}" class="btn btn-sm btn-primary mt-1">
                                            <x-svg-icon name="plus-lg" :size="14" /> @lang('discussion.btn_create_room')
                                        </a>
                                    </div>
                                </div>
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
