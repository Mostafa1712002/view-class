@extends('layouts.app')

@section('title', $topic->title)
@section('body_class', 'theme-light')

@php
    $isRtl   = app()->getLocale() === 'ar';
    $user    = auth()->user();
    $isStaff = $user && ($user->isSuperAdmin() || $user->isSchoolAdmin() || $user->isTeacher());
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $topic->title }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('discussion.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('discussion.index') }}">@lang('discussion.breadcrumb_rooms')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('discussion.room', $topic->room_id) }}">{{ optional($topic->room)->title }}</a></li>
                <li class="breadcrumb-item active">{{ $topic->title }}</li>
            </ol>
        </div>
    </div>
</div>


{{-- Topic body --}}
<div class="ds-card mb-2">
    <div class="ds-card-header d-flex justify-content-between align-items-start flex-wrap gap-1">
        <div>
            @if($topic->is_pinned)
                <span class="ds-badge-gold mr-1"><x-svg-icon name="pin-angle" :size="12" /> @lang('discussion.pinned_badge')</span>
            @endif
            @if($topic->is_closed)
                <span class="ds-badge-warning mr-1">@lang('discussion.closed_badge')</span>
            @endif
            <strong>{{ $topic->title }}</strong>
        </div>
        <div class="text-right">
            <small class="text-muted d-block mb-1">{{ optional($topic->creator)->name }} — {{ $topic->created_at->format('Y-m-d H:i') }}</small>
            @if($isStaff)
                {{-- Toggle comments on this topic (discussion.toggle_comments) --}}
                <form method="POST" action="{{ route('manage.discussion-rooms.topics.toggle-comments', $topic->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                            title="{{ $topic->comments_closed ? __('discussion.btn_enable_topic_comments') : __('discussion.btn_disable_topic_comments') }}"
                            aria-label="{{ $topic->comments_closed ? __('discussion.btn_enable_topic_comments') : __('discussion.btn_disable_topic_comments') }}">
                        <x-svg-icon name="{{ $topic->comments_closed ? 'slash-circle' : 'chat' }}" :size="14" />
                        {{ $topic->comments_closed ? __('discussion.btn_enable_topic_comments') : __('discussion.btn_disable_topic_comments') }}
                    </button>
                </form>
                {{-- Hide / show topic --}}
                <form method="POST" action="{{ route('manage.discussion-rooms.topics.hide', $topic->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                            title="{{ $topic->is_hidden ? __('discussion.btn_show') : __('discussion.btn_hide') }}"
                            aria-label="{{ $topic->is_hidden ? __('discussion.btn_show') : __('discussion.btn_hide') }}">
                        <x-svg-icon name="{{ $topic->is_hidden ? 'eye-slash' : 'eye' }}" :size="14" />
                    </button>
                </form>
            @endif
        </div>
    </div>
    <div class="ds-card-body">
        {!! nl2br(e($topic->body)) !!}
    </div>
</div>

{{-- Comments / Replies --}}
<div class="ds-card mb-2">
    <div class="ds-card-header">
        <h5 class="ds-card-title mb-0">
            <x-svg-icon name="chat-dots" :size="16" />
            @lang('discussion.field_comments_count'): {{ $topic->comments_count }}
        </h5>
    </div>
    <div class="ds-card-body p-0">
        @forelse($comments as $comment)
            <div class="border-bottom px-3 py-2" id="comment-{{ $comment->id }}">
                <div class="d-flex justify-content-between">
                    <div class="flex-grow-1">
                        <strong>{{ optional($comment->user)->name }}</strong>
                        <small class="text-muted ml-1">{{ $comment->created_at->diffForHumans() }}</small>
                        <p class="mb-0 mt-1">{!! nl2br(e($comment->body)) !!}</p>
                    </div>
                    <div class="text-nowrap pl-2">
                        {{-- Author self-delete --}}
                        @if($comment->user_id === $user->id)
                            <form method="POST"
                                  action="{{ route('discussion.comment.destroy', $comment->id) }}"
                                  class="d-inline"
                                  id="deleteComment{{ $comment->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="ds-action-btn text-danger"
                                        onclick="vcConfirm({ title: '{{ __('discussion.confirm_delete_comment') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteComment{{ $comment->id }}').submit(); } })"
                                        title="@lang('discussion.btn_delete')"
                                        aria-label="@lang('discussion.btn_delete')">
                                    <x-svg-icon name="trash" :size="15" />
                                </button>
                            </form>
                        @elseif($isStaff)
                            {{-- Staff delete via manage route --}}
                            <form method="POST"
                                  action="{{ route('manage.discussion-rooms.comments.destroy', $comment->id) }}"
                                  class="d-inline"
                                  id="staffDeleteComment{{ $comment->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="ds-action-btn text-danger"
                                        onclick="vcConfirm({ title: '{{ __('discussion.confirm_delete_comment') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('staffDeleteComment{{ $comment->id }}').submit(); } })"
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
            <div class="ds-empty py-3">
                <div class="ds-empty-icon"><x-svg-icon name="chat" :size="36" /></div>
                <div class="ds-empty-title">@lang('discussion.empty_comments')</div>
            </div>
        @endforelse
    </div>
</div>

{{-- Reply form (hidden if comments not allowed) --}}
@if($topic->is_closed)
    <div class="alert alert-secondary">
        <x-svg-icon name="lock" :size="15" /> @lang('discussion.topic_closed_notice')
    </div>
@elseif(!$topic->room->allow_comments)
    <div class="alert alert-secondary">
        <x-svg-icon name="slash-circle" :size="15" /> @lang('discussion.comments_disabled_notice')
    </div>
@elseif($topic->comments_closed)
    <div class="alert alert-secondary">
        <x-svg-icon name="slash-circle" :size="15" /> @lang('discussion.topic_comments_disabled_notice')
    </div>
@else
    <div class="ds-card">
        <div class="ds-card-header">
            <h5 class="ds-card-title mb-0"><x-svg-icon name="reply" :size="16" /> @lang('discussion.btn_reply')</h5>
        </div>
        <div class="ds-card-body">
            <form action="{{ route('discussion.comment.store', $topic->id) }}" method="POST" id="replyForm">
                @csrf
                <div class="form-group">
                    <textarea name="body" id="replyBody" rows="4"
                        class="form-control @error('body') is-invalid @enderror"
                        placeholder="{{ __('discussion.placeholder_body') }}"
                        required>{{ old('body') }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">
                    <x-svg-icon name="send" :size="15" /> @lang('discussion.btn_reply')
                </button>
            </form>
        </div>
    </div>
@endif
@endsection
