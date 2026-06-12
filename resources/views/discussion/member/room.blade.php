@extends('layouts.app')

@section('title', $room->title)
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
        <a href="{{ route('discussion.topic.create', $room->id) }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('discussion.btn_new_topic')
        </a>
    </div>
</div>

@include('components.alerts')

<div class="card">
    <div class="card-content">
        @forelse($topics as $topic)
            <div class="border-bottom px-3 py-2 {{ $topic->is_pinned ? 'bg-light' : '' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        @if($topic->is_pinned)
                            <span class="badge badge-warning mr-1"><i class="la la-thumbtack"></i> @lang('discussion.pinned_badge')</span>
                        @endif
                        @if($topic->is_closed)
                            <span class="badge badge-secondary mr-1">@lang('discussion.closed_badge')</span>
                        @endif
                        <a href="{{ route('discussion.topic', $topic->id) }}" class="font-weight-bold">
                            {{ $topic->title }}
                        </a>
                        <br>
                        <small class="text-muted">
                            {{ optional($topic->creator)->name }}
                            &mdash;
                            {{ $topic->created_at->diffForHumans() }}
                        </small>
                    </div>
                    <div class="text-right text-nowrap">
                        <span class="badge badge-light border mr-1">
                            <i class="la la-comment"></i> {{ $topic->comments_count }}
                        </span>
                        @if($isStaff)
                            {{-- Pin toggle --}}
                            <form method="POST"
                                  action="{{ route('manage.discussion-rooms.topics.pin', $topic->id) }}"
                                  class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs {{ $topic->is_pinned ? 'btn-warning' : 'btn-outline-warning' }}"
                                        title="{{ $topic->is_pinned ? __('discussion.btn_unpin') : __('discussion.btn_pin') }}">
                                    <i class="la la-thumbtack"></i>
                                </button>
                            </form>
                            {{-- Close topic --}}
                            @if(!$topic->is_closed)
                                <form method="POST"
                                      action="{{ route('manage.discussion-rooms.topics.close', $topic->id) }}"
                                      class="d-inline"
                                      id="closeTopic{{ $topic->id }}">
                                    @csrf
                                    <button type="button" class="btn btn-xs btn-outline-secondary"
                                            onclick="vcConfirm({ title: '{{ __('discussion.confirm_close_topic') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('closeTopic{{ $topic->id }}').submit(); } })"
                                            title="@lang('discussion.btn_close_topic')">
                                        <i class="la la-lock"></i>
                                    </button>
                                </form>
                            @endif
                            {{-- Delete topic --}}
                            <form method="POST"
                                  action="{{ route('manage.discussion-rooms.topics.destroy', $topic->id) }}"
                                  class="d-inline"
                                  id="deleteTopic{{ $topic->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-xs btn-outline-danger"
                                        onclick="vcConfirm({ title: '{{ __('discussion.confirm_delete_topic') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('deleteTopic{{ $topic->id }}').submit(); } })"
                                        title="@lang('discussion.btn_delete')">
                                    <i class="la la-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">
                <i class="la la-comment-dots la-3x mb-2 d-block"></i>
                @lang('discussion.empty_topics')
            </div>
        @endforelse

        @if($topics->hasPages())
            <div class="p-2">{{ $topics->links() }}</div>
        @endif
    </div>
</div>
@endsection
