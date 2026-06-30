@extends('layouts.app')

@section('title', $room->title)
@section('body_class', 'theme-light')

@php
    $isRtl     = app()->getLocale() === 'ar';
    $user      = auth()->user();
    $isStaff   = $user && ($user->isSuperAdmin() || $user->isSchoolAdmin() || $user->isTeacher());
    $canEdit   = $isStaff && $user->canDo('discussion.edit');
    $canReport = $isStaff && $user->canDo('discussion.view');
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $room->title }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('discussion.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('discussion.index') }}">@lang('discussion.breadcrumb_rooms')</a></li>
                <li class="breadcrumb-item active">{{ $room->title }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
        @if($room->allow_topics || $isStaff)
            <a href="{{ route('discussion.topic.create', $room->id) }}" class="btn btn-primary">
                <x-svg-icon name="plus-lg" :size="16" /> @lang('discussion.btn_new_topic')
            </a>
        @endif
    </div>
</div>


{{-- Room info + staff controls --}}
<div class="ds-card mb-2">
    <div class="ds-card-body">
        @if($room->description)
            <p class="mb-2">{{ $room->description }}</p>
        @endif
        @if($room->instructions)
            <div class="alert alert-info py-2 mb-2">
                <x-svg-icon name="info-circle" :size="15" /> {{ $room->instructions }}
            </div>
        @endif

        <div class="d-flex flex-wrap align-items-center" style="gap:.35rem 1.25rem">
            <span class="text-muted">
                <x-svg-icon name="person" :size="14" /> @lang('discussion.field_creator'):
                <strong>{{ $isRtl && optional($room->creator)->name_ar ? $room->creator->name_ar : optional($room->creator)->name }}</strong>
            </span>
            <span class="text-muted">
                <x-svg-icon name="clock" :size="14" /> @lang('discussion.field_created_at'):
                <strong>{{ $room->created_at->format('Y-m-d H:i') }}</strong>
            </span>
            <span class="text-muted">
                <x-svg-icon name="list-ul" :size="14" /> @lang('discussion.field_topics_count'):
                <strong>{{ $room->topics_count }}</strong>
            </span>
            @if(optional($room->subject)->name)
                <span class="text-muted">
                    <x-svg-icon name="book" :size="14" /> @lang('discussion.field_subject'):
                    <strong>{{ $room->subject->name }}</strong>
                </span>
            @endif
            @if($room->category)
                <span class="text-muted">
                    <x-svg-icon name="tag" :size="14" /> @lang('discussion.field_category'):
                    <strong>{{ $room->category }}</strong>
                </span>
            @endif
        </div>

        @if(!$room->allow_topics)
            <div class="alert alert-secondary py-2 mt-2 mb-0">
                <x-svg-icon name="slash-circle" :size="15" /> @lang('discussion.topics_stopped_notice')
            </div>
        @endif

        @if($canEdit || $canReport)
            <div class="mt-2 d-flex flex-wrap" style="gap:.5rem">
                @if($canEdit)
                    {{-- Stop / allow new topics (toggle allow_topics) --}}
                    <form method="POST" action="{{ route('manage.discussion-rooms.toggle-topics', $room->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $room->allow_topics ? 'btn-outline-warning' : 'btn-outline-success' }}">
                            <x-svg-icon name="{{ $room->allow_topics ? 'slash-circle' : 'plus-lg' }}" :size="14" />
                            {{ $room->allow_topics ? __('discussion.btn_stop_topics') : __('discussion.btn_enable_topics') }}
                        </button>
                    </form>
                @endif
                @if($canReport)
                    <a href="{{ route('manage.discussion-rooms.report', $room->id) }}" class="btn btn-sm btn-outline-secondary">
                        <x-svg-icon name="bar-chart" :size="14" /> @lang('discussion.btn_room_report')
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="ds-card">
    <div class="ds-card-header">
        <span class="ds-card-title"><x-svg-icon name="chat-square-dots" :size="16" /> @lang('discussion.field_topics_count')</span>
    </div>
    <div class="ds-card-body p-0">
        @forelse($topics as $topic)
            <div class="border-bottom px-3 py-2 {{ $topic->is_pinned ? 'bg-light' : '' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        @if($topic->is_pinned)
                            <span class="ds-badge-gold mr-1"><x-svg-icon name="pin-angle" :size="12" /> @lang('discussion.pinned_badge')</span>
                        @endif
                        @if($topic->is_closed)
                            <span class="ds-badge-warning mr-1">@lang('discussion.closed_badge')</span>
                        @endif
                        @if($topic->comments_closed)
                            <span class="badge badge-light border mr-1"><x-svg-icon name="slash-circle" :size="12" /></span>
                        @endif
                        @if($topic->is_hidden)
                            <span class="ds-badge-danger mr-1"><x-svg-icon name="eye-slash" :size="12" /> @lang('discussion.btn_hide')</span>
                        @endif
                        <a href="{{ route('discussion.topic', $topic->id) }}" class="font-weight-bold">
                            {{ $topic->title }}
                        </a>
                        <br>
                        <small class="text-muted">
                            {{ optional($topic->creator)->name }}
                            @if(optional($topic->creator)->role_name)
                                <span class="badge badge-light border">{{ $topic->creator->role_name }}</span>
                            @endif
                            &mdash;
                            {{ $topic->created_at->diffForHumans() }}
                        </small>
                    </div>
                    <div class="text-right text-nowrap">
                        <span class="ds-badge-navy mr-1">
                            <x-svg-icon name="chat" :size="13" /> {{ $topic->comments_count }}
                        </span>
                        @if($isStaff)
                            {{-- Pin toggle --}}
                            <form method="POST"
                                  action="{{ route('manage.discussion-rooms.topics.pin', $topic->id) }}"
                                  class="d-inline">
                                @csrf
                                <button type="submit" class="ds-action-btn {{ $topic->is_pinned ? 'text-warning' : '' }}"
                                        title="{{ $topic->is_pinned ? __('discussion.btn_unpin') : __('discussion.btn_pin') }}"
                                        aria-label="{{ $topic->is_pinned ? __('discussion.btn_unpin') : __('discussion.btn_pin') }}">
                                    <x-svg-icon name="pin-angle" :size="15" />
                                </button>
                            </form>
                            {{-- Close topic --}}
                            @if(!$topic->is_closed)
                                <form method="POST"
                                      action="{{ route('manage.discussion-rooms.topics.close', $topic->id) }}"
                                      class="d-inline"
                                      id="closeTopic{{ $topic->id }}">
                                    @csrf
                                    <button type="button" class="ds-action-btn"
                                            onclick="vcConfirm({ title: '{{ __('discussion.confirm_close_topic') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('closeTopic{{ $topic->id }}').submit(); } })"
                                            title="@lang('discussion.btn_close_topic')"
                                            aria-label="@lang('discussion.btn_close_topic')">
                                        <x-svg-icon name="lock" :size="15" />
                                    </button>
                                </form>
                            @endif
                            {{-- Toggle comments on this topic (discussion.toggle_comments) --}}
                            <form method="POST"
                                  action="{{ route('manage.discussion-rooms.topics.toggle-comments', $topic->id) }}"
                                  class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="ds-action-btn {{ $topic->comments_closed ? 'text-danger' : '' }}"
                                        title="{{ $topic->comments_closed ? __('discussion.btn_enable_topic_comments') : __('discussion.btn_disable_topic_comments') }}"
                                        aria-label="{{ $topic->comments_closed ? __('discussion.btn_enable_topic_comments') : __('discussion.btn_disable_topic_comments') }}">
                                    <x-svg-icon name="{{ $topic->comments_closed ? 'slash-circle' : 'chat' }}" :size="15" />
                                </button>
                            </form>
                            {{-- Hide / show topic --}}
                            <form method="POST"
                                  action="{{ route('manage.discussion-rooms.topics.hide', $topic->id) }}"
                                  class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="ds-action-btn"
                                        title="{{ $topic->is_hidden ? __('discussion.btn_show') : __('discussion.btn_hide') }}"
                                        aria-label="{{ $topic->is_hidden ? __('discussion.btn_show') : __('discussion.btn_hide') }}">
                                    <x-svg-icon name="{{ $topic->is_hidden ? 'eye-slash' : 'eye' }}" :size="15" />
                                </button>
                            </form>
                            {{-- Delete topic --}}
                            <form method="POST"
                                  action="{{ route('manage.discussion-rooms.topics.destroy', $topic->id) }}"
                                  class="d-inline"
                                  id="deleteTopic{{ $topic->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="ds-action-btn text-danger"
                                        onclick="vcConfirm({ title: '{{ __('discussion.confirm_delete_topic') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteTopic{{ $topic->id }}').submit(); } })"
                                        title="@lang('discussion.btn_delete')"
                                        aria-label="@lang('discussion.btn_delete')">
                                    <x-svg-icon name="trash" :size="15" />
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="chat-square-dots" :size="48" /></div>
                <div class="ds-empty-title">@lang('discussion.empty_topics')</div>
                @if($room->allow_topics || $isStaff)
                    <div class="ds-empty-desc">
                        <a href="{{ route('discussion.topic.create', $room->id) }}" class="btn btn-sm btn-primary mt-1">
                            <x-svg-icon name="plus-lg" :size="14" /> @lang('discussion.btn_new_topic')
                        </a>
                    </div>
                @endif
            </div>
        @endforelse

        @if($topics->hasPages())
            <div class="p-2">{{ $topics->links() }}</div>
        @endif
    </div>
</div>
@endsection
