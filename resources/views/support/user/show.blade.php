@extends('layouts.app')

@section('title', __('support.ticket_detail_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            #{{ $ticket->id }} — {{ $ticket->subject }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('support.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('my.support.index') }}">@lang('support.breadcrumb_my_tickets')</a></li>
                <li class="breadcrumb-item active">#{{ $ticket->id }}</li>
            </ol>
        </div>
    </div>
</div>


{{-- Ticket details card --}}
<div class="card mb-2">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="card-title mb-0">@lang('support.section_ticket_details')</h4>
        <div>
            <span class="badge badge-{{ $ticket->statusColor() }} mr-1">{{ $ticket->statusLabel() }}</span>
            <span class="badge badge-{{ $ticket->priorityColor() }}">{{ $ticket->priorityLabel() }}</span>
        </div>
    </div>
    <div class="card-content">
        <div class="card-body">
            <div class="row mb-1">
                <div class="col-md-3 font-weight-bold">@lang('support.field_type')</div>
                <div class="col-md-9">{{ $ticket->typeLabel() }}</div>
            </div>
            <div class="row mb-1">
                <div class="col-md-3 font-weight-bold">@lang('support.field_category')</div>
                <div class="col-md-9">{{ __('support.category_' . $ticket->category) }}</div>
            </div>
            <div class="row mb-1">
                <div class="col-md-3 font-weight-bold">@lang('support.field_department')</div>
                <div class="col-md-9">{{ $ticket->departmentLabel() }}</div>
            </div>
            <div class="row mb-1">
                <div class="col-md-3 font-weight-bold">@lang('support.field_created_at')</div>
                <div class="col-md-9">{{ $ticket->created_at->format('Y-m-d H:i') }}</div>
            </div>
            @if($ticket->problem_url)
            <div class="row mb-1">
                <div class="col-md-3 font-weight-bold">@lang('support.field_problem_url')</div>
                <div class="col-md-9"><a href="{{ $ticket->problem_url }}" target="_blank" rel="noopener">{{ $ticket->problem_url }}</a></div>
            </div>
            @endif
            @if($ticket->attachment_path)
            <div class="row mb-1">
                <div class="col-md-3 font-weight-bold">@lang('support.field_attachments')</div>
                <div class="col-md-9">
                    <a href="{{ route('my.support.attachment', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                        <x-svg-icon name="paperclip" size="14" /> @lang('support.btn_download_attachment')
                    </a>
                </div>
            </div>
            @endif
            @if($ticket->assignee)
            <div class="row mb-1">
                <div class="col-md-3 font-weight-bold">@lang('support.field_assigned_to')</div>
                <div class="col-md-9">{{ $ticket->assignee->name }}</div>
            </div>
            @endif
            <hr>
            <div class="mt-1">{!! nl2br(e($ticket->body)) !!}</div>
        </div>
    </div>
</div>

{{-- Replies thread --}}
@if($ticket->replies->count())
<div class="card mb-2">
    <div class="card-header">
        <h4 class="card-title">@lang('support.section_replies')</h4>
    </div>
    <div class="card-content">
        <div class="card-body p-0">
            @foreach($ticket->replies as $reply)
            <div class="p-2 border-bottom {{ $reply->is_staff ? 'bg-light-primary' : '' }}">
                <div class="d-flex align-items-center mb-1">
                    <strong>{{ optional($reply->user)->name }}</strong>
                    @if($reply->is_staff)
                        <span class="badge badge-info ml-1">@lang('support.badge_staff')</span>
                    @endif
                    <small class="text-muted ml-auto">{{ $reply->created_at->format('Y-m-d H:i') }}</small>
                </div>
                <div>{!! nl2br(e($reply->body)) !!}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Reply form (only if not closed) --}}
@if(!in_array($ticket->status, ['resolved', 'closed']))
<div class="card">
    <div class="card-header">
        <h4 class="card-title">@lang('support.section_add_reply')</h4>
    </div>
    <div class="card-content">
        <div class="card-body">
            <form action="{{ route('my.support.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="body">@lang('support.field_reply_body') <span class="text-danger">*</span></label>
                    <textarea name="body" id="body" rows="4"
                        class="form-control @error('body') is-invalid @enderror"
                        required>{{ old('body') }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="attachment">@lang('support.field_attachment')</label>
                    <input type="file" name="attachment" id="attachment"
                        class="form-control-file @error('attachment') is-invalid @enderror">
                    <small class="text-muted">@lang('support.attachment_hint')</small>
                    @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">
                    <x-svg-icon name="send" size="15" /> @lang('support.btn_send_reply')
                </button>
            </form>
        </div>
    </div>
</div>
@else
<div class="alert alert-secondary">@lang('support.ticket_closed_notice')</div>
@endif
@endsection
