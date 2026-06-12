@extends('layouts.app')

@section('title', __('discussion.page_title_rooms'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('discussion.page_title_rooms')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('discussion.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('discussion.breadcrumb_rooms')</li>
            </ol>
        </div>
    </div>
</div>

@include('components.alerts')

<div class="card">
    <div class="card-content">
        @forelse($rooms as $room)
            <div class="border-bottom px-3 py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <a href="{{ route('discussion.room', $room->id) }}" class="font-weight-bold h5 mb-0">
                            <i class="la la-comments text-primary mr-1"></i>{{ $room->title }}
                        </a>
                        @if($room->description)
                            <p class="text-muted small mb-1">{{ $room->description }}</p>
                        @endif
                    </div>
                    <div class="text-right text-nowrap">
                        <span class="badge badge-light border">
                            <i class="la la-list-alt"></i> {{ $room->topics_count }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">
                <i class="la la-comments la-3x mb-2 d-block"></i>
                @lang('discussion.empty_rooms')
            </div>
        @endforelse

        @if($rooms->hasPages())
            <div class="p-2">{{ $rooms->links() }}</div>
        @endif
    </div>
</div>
@endsection
