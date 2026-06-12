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

@include('components.alerts')

{{-- Topic body --}}
<div class="card mb-2">
    <div class="card-header d-flex justify-content-between align-items-start">
        <div>
            @if($topic->is_pinned)
                <span class="badge badge-warning mr-1"><i class="la la-thumbtack"></i> @lang('discussion.pinned_badge')</span>
            @endif
            @if($topic->is_closed)
                <span class="badge badge-secondary mr-1">@lang('discussion.closed_badge')</span>
            @endif
            <strong>{{ $topic->title }}</strong>
        </div>
        <small class="text-muted">{{ optional($topic->creator)->name }} — {{ $topic->created_at->format('Y-m-d H:i') }}</small>
    </div>
    <div class="card-content">
        <div class="card-body">
            {!! nl2br(e($topic->body)) !!}
        </div>
    </div>
</div>

{{-- Comments / Replies --}}
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="la la-comments"></i>
            @lang('discussion.field_comments_count'): {{ $topic->comments_count }}
        </h5>
    </div>
    <div class="card-content">
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
                                <button type="button" class="btn btn-xs btn-outline-danger"
                                        onclick="vcConfirm({ title: '{{ __('discussion.confirm_delete_comment') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteComment{{ $comment->id }}').submit(); } })"
                                        title="@lang('discussion.btn_delete')">
                                    <i class="la la-trash"></i>
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
                                <button type="button" class="btn btn-xs btn-outline-danger"
                                        onclick="vcConfirm({ title: '{{ __('discussion.confirm_delete_comment') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('staffDeleteComment{{ $comment->id }}').submit(); } })"
                                        title="@lang('discussion.btn_delete')">
                                    <i class="la la-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-3">
                @lang('discussion.empty_comments')
            </div>
        @endforelse
    </div>
</div>

{{-- Reply form (hidden if topic closed) --}}
@if($topic->is_closed)
    <div class="alert alert-secondary">
        <i class="la la-lock"></i> @lang('discussion.topic_closed_notice')
    </div>
@else
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="la la-reply"></i> @lang('discussion.btn_reply')</h5>
        </div>
        <div class="card-content">
            <div class="card-body">
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
                        <i class="la la-paper-plane"></i> @lang('discussion.btn_reply')
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
