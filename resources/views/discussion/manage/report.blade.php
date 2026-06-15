@extends('layouts.app')

@section('title', __('discussion.page_title_report'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $room  = $report['room'];
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('discussion.page_title_report')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('discussion.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.discussion-rooms.index') }}">@lang('discussion.breadcrumb_manage')</a></li>
                <li class="breadcrumb-item active">{{ $room->title }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
        <a href="{{ route('discussion.room', $room->id) }}" class="btn btn-secondary">
            <i class="la la-eye"></i> @lang('discussion.btn_view')
        </a>
    </div>
</div>


{{-- Stat cards --}}
<div class="row">
    <div class="col-md-3 col-6 mb-2">
        <div class="card text-center h-100">
            <div class="card-content"><div class="card-body py-3">
                <i class="la la-list-alt la-2x text-primary"></i>
                <h3 class="mb-0 mt-1">{{ $report['topic_count'] }}</h3>
                <small class="text-muted">@lang('discussion.field_topics_count')</small>
            </div></div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card text-center h-100">
            <div class="card-content"><div class="card-body py-3">
                <i class="la la-comments la-2x text-info"></i>
                <h3 class="mb-0 mt-1">{{ $report['comment_count'] }}</h3>
                <small class="text-muted">@lang('discussion.field_comments_total')</small>
            </div></div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card text-center h-100">
            <div class="card-content"><div class="card-body py-3">
                <i class="la la-users la-2x text-success"></i>
                <h3 class="mb-0 mt-1">{{ $report['participant_count'] }}</h3>
                <small class="text-muted">@lang('discussion.field_participants')</small>
            </div></div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card text-center h-100">
            <div class="card-content"><div class="card-body py-3">
                <i class="la la-clock la-2x text-warning"></i>
                <h5 class="mb-0 mt-1">{{ $report['last_activity_at'] ? $report['last_activity_at']->diffForHumans() : '—' }}</h5>
                <small class="text-muted">@lang('discussion.field_last_activity')</small>
            </div></div>
        </div>
    </div>
</div>

{{-- Top topics --}}
<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $room->title }}</h4>
    </div>
    <div class="card-content">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('discussion.field_title')</th>
                        <th>@lang('discussion.field_author')</th>
                        <th>@lang('discussion.field_comments_count')</th>
                        <th>@lang('discussion.field_last_activity')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['top_topics'] as $topic)
                        <tr>
                            <td>{{ $topic->id }}</td>
                            <td><a href="{{ route('discussion.topic', $topic->id) }}">{{ $topic->title }}</a></td>
                            <td>{{ optional($topic->creator)->name }}</td>
                            <td>{{ $topic->comments_count }}</td>
                            <td><small class="text-muted">{{ $topic->last_activity_at ? $topic->last_activity_at->diffForHumans() : '—' }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">@lang('discussion.empty_topics')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
