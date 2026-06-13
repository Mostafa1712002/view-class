@extends('layouts.app')

@section('title', $message->subject)
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';

    $importanceColor = match($message->importance) {
        'urgent'    => 'badge-danger',
        'important' => 'badge-warning',
        default     => 'badge-secondary',
    };
    $importanceLabel = match($message->importance) {
        'urgent'    => __('mailbox.urgent'),
        'important' => __('mailbox.important_label'),
        default     => __('mailbox.normal'),
    };
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $message->subject }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('mailbox.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('my.mailbox.index') }}">@lang('mailbox.breadcrumb_mailbox')</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($message->subject, 40) }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }}">
        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            <i class="la la-arrow-left"></i> @lang('mailbox.back')
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:8px;">
        <div>
            <span class="badge {{ $importanceColor }} mr-1">{{ $importanceLabel }}</span>
            <strong>{{ $message->subject }}</strong>
        </div>
        <div class="d-flex flex-wrap" style="gap:6px;">
            @if($recipientRow)
                {{-- Star / Unstar --}}
                @if($recipientRow->starred)
                    <form action="{{ route('my.mailbox.unstar', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="la la-star"></i> @lang('mailbox.unstarred_action')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.star', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">
                            <i class="la la-star"></i> @lang('mailbox.starred_action')
                        </button>
                    </form>
                @endif

                {{-- Task toggle --}}
                <form action="{{ route('my.mailbox.task', $message->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $recipientRow->is_task ? 'btn-success' : 'btn-outline-success' }}">
                        <i class="la la-tasks"></i>
                        {{ $recipientRow->is_task ? __('mailbox.unmark_task') : __('mailbox.mark_task') }}
                    </button>
                </form>

                {{-- Archive / Unarchive --}}
                @if($recipientRow->archived)
                    <form action="{{ route('my.mailbox.unarchive', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary">
                            <i class="la la-box-open"></i> @lang('mailbox.unarchived_action')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.archive', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="la la-archive"></i> @lang('mailbox.archived_action')
                        </button>
                    </form>
                @endif

                {{-- Trash --}}
                @if(! $recipientRow->trashed)
                    <form action="{{ route('my.mailbox.trash', $message->id) }}" method="POST" class="d-inline" id="trash-show-form">
                        @csrf
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="
                                    if(window.vcConfirm){
                                        window.vcConfirm({title:'@lang('mailbox.move_to_trash')?'}).then(r=>{if(r.isConfirmed)document.getElementById('trash-show-form').submit();});
                                    } else {
                                        document.getElementById('trash-show-form').submit();
                                    }">
                            <i class="la la-trash"></i> @lang('mailbox.move_to_trash')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.restore', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-info">
                            <i class="la la-undo"></i> @lang('mailbox.restore')
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <div class="card-content">
        <div class="card-body">
            {{-- Meta --}}
            <table class="table table-borderless table-sm mb-3" style="max-width:600px;">
                <tr>
                    <th class="text-muted pr-3" style="width:80px;">@lang('mailbox.from')</th>
                    <td>{{ $message->sender->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="text-muted pr-3">@lang('mailbox.to')</th>
                    <td>
                        @foreach($message->recipients as $r)
                            <span class="badge badge-light mr-1">{{ $r->recipient->name ?? '—' }}</span>
                        @endforeach
                    </td>
                </tr>
                @if($message->relatedStudent)
                    <tr>
                        <th class="text-muted pr-3">@lang('mailbox.related_student')</th>
                        <td>{{ $message->relatedStudent->name }}</td>
                    </tr>
                @endif
                <tr>
                    <th class="text-muted pr-3">@lang('mailbox.date')</th>
                    <td>{{ $message->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            </table>

            <hr>

            {{-- Body --}}
            <div class="message-body" style="white-space: pre-wrap; line-height:1.8;">{{ $message->body }}</div>

            {{-- Attachment --}}
            @if($message->attachment_path)
                <hr>
                <div class="mt-2">
                    <i class="la la-paperclip text-muted"></i>
                    <a href="{{ Storage::disk('public')->url($message->attachment_path) }}"
                       target="_blank" class="btn btn-sm btn-outline-primary ml-1">
                        <i class="la la-download"></i> @lang('mailbox.download')
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
